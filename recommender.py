"""
recommender.py  —  Hybrid Recommender (User-Based CF + Cosine Similarity CBF)

Fully matches proposal:
  - Formula 3.1 : Cosine Similarity CBF
  - Formula 3.2 : Mean-centered User-Based CF with KNN (cosine similarity)
  - Section 3.5.5: Weighted hybrid combination
  - Formula 3.3 : Accuracy   = (TP+TN) / (TP+FP+TN+FN)
  - Formula 3.4 : Precision  = TP / (TP+FP)
  - Formula 3.5 : Recall     = TP / (TP+FN)
  - Formula 3.6 : F1-Score   = 2 × Precision × Recall / (Precision + Recall)

Confirmed DB schema
────────────────────
product        : productID (PK), Product_Name, Brand, Product_Type, image_url, ...
favorite       : favoriteID (PK), userID, productID, rating, created_at
feedback       : feedbackID (PK), userID, productID, rating, comment, feedbackDate
recommendation : recommendationID (PK auto_increment), userID, productID,
                 recommendation_score, reason, created_at
"""

import json
import argparse
import numpy as np
import pandas as pd
from sklearn.preprocessing import MinMaxScaler
from sklearn.metrics.pairwise import cosine_similarity
from sqlalchemy import create_engine, text

# ─────────────────────────────────────────────
# 1. DATABASE CONFIG  ← must match your db.php
# ─────────────────────────────────────────────
DB_HOST     = "127.0.0.1"
DB_USER     = "root"
DB_PASSWORD = ""
DB_NAME     = "greenchoice7"  

def get_engine():
    url = f"mysql+mysqlconnector://{DB_USER}:{DB_PASSWORD}@{DB_HOST}/{DB_NAME}"
    return create_engine(url)


# ─────────────────────────────────────────────
# 2. FEATURE COLUMNS FOR CBF
# ─────────────────────────────────────────────
# Numerical — MinMax scaled to [0, 1]
NUMERICAL_FEATURES = [
    "CPU_Cores", "CPU_Base_GHz", "CPU_Max_GHz", "RAM_GB",
    "Display_Size_in", "Display_Resolution_MP", "Battery_Wh",
    "Off_Mode_W", "Sleep_Mode_W", "Short_Idle_W", "TEC_kWh",
    "Annual_Energy_kWh", "Volume_cuft", "Height_in", "Width_in",
    "Depth_in", "IMEF", "IWF", "Annual_Water_gal", "Drum_Capacity_cuft",
    "Voltage_V", "CEF", "Place_Settings", "Water_Use_gal_cycle",
    "Total_Volume_cuft", "Pct_Better_Federal_Std", "rating",
]

# Categorical — one-hot encoded
CATEGORICAL_FEATURES = [
    "Product_Type", "Sub_Type", "Brand",
    "Processor_Brand", "Processor_Name", "Storage_Type", "OS",
    "Touch_Screen", "EPEAT_Tier", "EPEAT_Certified",
    "EnergyStar_Certified", "Connected", "Heat_Pump_Technology",
    "Vented_or_Ventless", "Soil_Sensing", "Tub_Material",
    "Drying_Method", "Ice_Maker", "Connected_Functionality",
]

# Weights for eco_score computation (cold-start fallback)
ECO_WEIGHTS = {
    "Pct_Better_Federal_Std": 0.35,   # higher = better
    "TEC_kWh":                -0.25,  # lower  = better
    "Annual_Energy_kWh":      -0.20,  # lower  = better
    "Annual_Water_gal":       -0.10,  # lower  = better
    "rating":                  0.10,  # product rating
}


# ─────────────────────────────────────────────
# 3. DB HELPERS
# ─────────────────────────────────────────────
def load_products(engine) -> pd.DataFrame:
    with engine.connect() as conn:
        df = pd.read_sql(text("SELECT * FROM product"), conn)
    return _compute_eco_score(df)


def _compute_eco_score(df: pd.DataFrame) -> pd.DataFrame:
    """
    Derive normalised eco_score (0–100) from energy/water columns.
    Used for cold-start fallback ranking when system has no interactions.
    """
    score  = pd.Series(0.0, index=df.index)
    scaler = MinMaxScaler()

    for col, weight in ECO_WEIGHTS.items():
        if col not in df.columns:
            continue
        vals   = pd.to_numeric(df[col], errors="coerce").fillna(0).values.reshape(-1, 1)
        normed = scaler.fit_transform(vals).flatten()
        if weight < 0:
            normed = 1 - normed   # invert: lower raw = higher eco score
        score += abs(weight) * normed

    # Certification bonuses
    if "EPEAT_Certified" in df.columns:
        score += 0.05 * df["EPEAT_Certified"].isin(["Yes", "yes", "1", 1, True]).astype(float)
    if "EnergyStar_Certified" in df.columns:
        score += 0.05 * df["EnergyStar_Certified"].isin(["Yes", "yes", "1", 1, True]).astype(float)

    df["eco_score"] = (score * 100).round(2)
    return df


def load_interactions(engine) -> pd.DataFrame:
    """
    Unified interaction table from two sources:

    1. favorite — bookmarks
       rating=1 (default/unrated) → implicit score 4
       rating>1 (explicitly rated) → use actual value

    2. feedback — explicit star ratings (1–5) from product reviews

    Final score = average when same user-product pair appears in both.
    Matches proposal: interactions include bookmarks/favorites AND reviews.
    """
    with engine.connect() as conn:
        favorites = pd.read_sql(
            text("""
                SELECT userID                         AS user_id,
                       productID                      AS product_id,
                       COALESCE(NULLIF(rating, 1), 4) AS score
                FROM   favorite
            """),
            conn,
        )
        feedback = pd.read_sql(
            text("""
                SELECT userID    AS user_id,
                       productID AS product_id,
                       rating    AS score
                FROM   feedback
                WHERE  rating IS NOT NULL AND rating > 0
            """),
            conn,
        )

    interactions = pd.concat([favorites, feedback], ignore_index=True)
    interactions = interactions.groupby(
        ["user_id", "product_id"], as_index=False
    )["score"].mean()

    # Ensure correct dtypes to avoid concat/merge issues
    interactions["user_id"]    = interactions["user_id"].astype(int)
    interactions["product_id"] = interactions["product_id"].astype(int)
    interactions["score"]      = interactions["score"].astype(float)

    return interactions


# ─────────────────────────────────────────────
# 4. CONTENT-BASED FILTERING (CBF)
#
#    Formula 3.1:
#    Cosine Similarity(A, B) = A · B / (‖A‖ · ‖B‖)
#
#    Where:
#      A = User Profile Vector
#          (weighted average of interacted product feature vectors)
#      B = Eco Features Vector
#          (each product's feature vector — numerical + categorical)
# ─────────────────────────────────────────────
def build_content_matrix(products: pd.DataFrame):
    """
    Build the product feature matrix (B vectors).
    Returns (feature_matrix [n_products × n_features], productID_list).
    """
    df = products.set_index("productID")

    # Numerical features → MinMax scaled to [0, 1]
    num_cols    = [c for c in NUMERICAL_FEATURES if c in df.columns]
    num_df      = df[num_cols].apply(pd.to_numeric, errors="coerce").fillna(0)
    num_scaled  = MinMaxScaler().fit_transform(num_df) if not num_df.empty else np.zeros((len(df), 0))

    # Categorical features → one-hot encoded
    cat_cols    = [c for c in CATEGORICAL_FEATURES if c in df.columns]
    cat_df      = df[cat_cols].fillna("Unknown").astype(str)
    cat_encoded = pd.get_dummies(cat_df)

    feature_matrix = np.hstack([num_scaled, cat_encoded.values])
    return feature_matrix, df.index.tolist()


def cbf_scores(user_id: int, products: pd.DataFrame, interactions: pd.DataFrame) -> pd.Series:
    """
    Formula 3.1 — Cosine Similarity CBF.

    Steps:
      1. Build feature matrix (B) for all products
      2. Build user profile vector (A) as weighted average of
         interacted product vectors
      3. Compute cosine similarity: A·B / (‖A‖·‖B‖) for every product
    Returns 0 for all products if user has no interactions.
    """
    feature_matrix, product_ids = build_content_matrix(products)
    pid_index = {pid: i for i, pid in enumerate(product_ids)}

    user_rows = interactions[interactions["user_id"] == user_id]
    if user_rows.empty:
        return pd.Series(0.0, index=product_ids)

    # Step 2 — Build user profile vector A
    profile = np.zeros(feature_matrix.shape[1])
    total_w = 0.0
    for _, row in user_rows.iterrows():
        pid = int(row["product_id"])
        if pid in pid_index:
            w        = float(row["score"])
            profile += w * feature_matrix[pid_index[pid]]
            total_w += w

    if total_w == 0:
        return pd.Series(0.0, index=product_ids)

    profile /= total_w  # normalise to weighted average

    # Step 3 — Formula 3.1: A·B / (‖A‖·‖B‖) for every product B
    sims = cosine_similarity(profile.reshape(1, -1), feature_matrix)[0]
    return pd.Series(sims, index=product_ids)


# ─────────────────────────────────────────────
# 5. USER-BASED COLLABORATIVE FILTERING (CF)
#
#    Formula 3.2 — Mean-Centered KNN:
#
#    R̂(u,i) = R̄(u) + Σ[sim(u,v) × (R(v,i) − R̄(v))]
#                     ─────────────────────────────────
#                              Σ|sim(u,v)|
#
#    Where:
#      R̂(u,i)  = predicted score for user u on product i
#      R̄(u)    = average rating of user u  (ignoring unrated)
#      R̄(v)    = average rating of neighbor v (ignoring unrated)
#      N(u)    = set of top-K similar users (neighbors)
#      sim(u,v) = cosine similarity between user u and user v
# ─────────────────────────────────────────────
def cf_scores(user_id: int, interactions: pd.DataFrame, n_neighbors: int = 10) -> pd.Series:
    """
    Formula 3.2 — Mean-centered User-Based CF with KNN.

    Existing user: cosine similarity → top-K neighbors → mean-centered prediction.
    New user:      product popularity (bookmark count × avg score) so heavily
                   bookmarked products surface first for cold-start users.
    """
    matrix = interactions.pivot_table(
        index="user_id", columns="product_id", values="score", fill_value=0
    )

    if matrix.empty:
        return pd.Series(0.0, dtype=float)

    all_product_cols = matrix.columns

    # ── New user: not in interaction matrix ──
    # Rank by product popularity across ALL users:
    # 60% how many users bookmarked it + 40% average rating received.
    # Ensures heavily bookmarked products (e.g. Apple iPhone: 48 users) score highest.
    if user_id not in matrix.index:
        product_count = (matrix > 0).sum(axis=0)
        product_avg   = matrix.replace(0, np.nan).mean(axis=0).fillna(0)
        count_norm    = product_count / (product_count.max() + 1e-9)
        avg_norm      = product_avg   / (product_avg.max()   + 1e-9)
        predicted     = 0.6 * count_norm + 0.4 * avg_norm
        return predicted

    # ── Existing user ──
    if len(matrix.index) < 2:
        return pd.Series(0.0, index=all_product_cols)

    # Step 1 — cosine similarity between target user and all others
    user_vec   = matrix.loc[user_id].values.reshape(1, -1)
    sims       = cosine_similarity(user_vec, matrix.values)[0]
    sim_series = pd.Series(sims, index=matrix.index).drop(user_id, errors="ignore")

    # Step 2 — top-K neighbors N(u)
    top_neighbors = sim_series.nlargest(n_neighbors)
    if top_neighbors.sum() == 0:
        return pd.Series(0.0, index=all_product_cols)

    weights         = top_neighbors.values.reshape(-1, 1)
    neighbor_matrix = matrix.loc[top_neighbors.index]

    # Step 3 — mean-centering (Formula 3.2)
    # R̄(u) — target user's average rating (0s excluded = not rated)
    user_mean = matrix.loc[user_id].replace(0, np.nan).mean()
    if pd.isna(user_mean):
        user_mean = 0.0

    # R̄(v) — each neighbor's average rating (0s excluded)
    neighbor_means = neighbor_matrix.replace(0, np.nan).mean(axis=1)

    # R(v,i) − R̄(v) — remove each neighbor's personal rating bias
    mean_centered = neighbor_matrix.sub(neighbor_means, axis=0).fillna(0)

    # R̂(u,i) = R̄(u) + Σ[sim(u,v) × (R(v,i) − R̄(v))] / Σ|sim(u,v)|
    predicted = user_mean + (mean_centered * weights).sum(axis=0) / (weights.sum() + 1e-9)

    # Zero out products the user already interacted with
    seen = interactions[interactions["user_id"] == user_id]["product_id"].tolist()
    predicted[predicted.index.isin(seen)] = 0.0

    return predicted


# ─────────────────────────────────────────────
# 6. HYBRID COMBINATION (Section 3.5.5)
#
#    Final Score = alpha × CF_score + (1 − alpha) × CBF_score
#
#    alpha = 0.5 → balanced hybrid (default)
#    alpha = 1.0 → pure CF
#    alpha = 0.0 → pure CBF
# ─────────────────────────────────────────────
def hybrid_recommend(
    user_id: int,
    products: pd.DataFrame,
    interactions: pd.DataFrame,
    top_n: int = 12,
    alpha: float = 0.5,
) -> list:
    """
    Weighted hybrid: CBF (Formula 3.1) + CF (Formula 3.2).
    Both scores normalised to [0,1] before combining.
    """
    product_ids = products["productID"].tolist()
    n_users     = interactions["user_id"].nunique()
    user_seen   = set(interactions[interactions["user_id"] == user_id]["product_id"].tolist())
    n_seen      = len(user_seen)

    # Force pure CBF if only 1 user exists — CF needs neighbors
    effective_alpha = alpha if n_users >= 2 else 0.0

    # CF scores (Formula 3.2) — normalised to [0,1]
    cf = cf_scores(user_id, interactions).reindex(product_ids, fill_value=0.0)
    cf = (cf - cf.min()) / (cf.max() - cf.min() + 1e-9)

    # CBF scores (Formula 3.1) — normalised to [0,1]
    cbf = cbf_scores(user_id, products, interactions).reindex(product_ids, fill_value=0.0)
    cbf = (cbf - cbf.min()) / (cbf.max() - cbf.min() + 1e-9)

    # Weighted combination (Section 3.5.5)
    hybrid = effective_alpha * cf + (1 - effective_alpha) * cbf

    # Exclude already-seen products only when user has 3+ interactions
    # (with fewer interactions excluding them leaves too few results)
    if n_seen >= 3:
        hybrid[hybrid.index.isin(user_seen)] = 0.0

    top_ids = hybrid.nlargest(top_n).index.tolist()
    result  = products[products["productID"].isin(top_ids)].copy()
    result["hybrid_score"] = result["productID"].map(hybrid)
    result  = result.sort_values("hybrid_score", ascending=False)

    return result.to_dict(orient="records")


# ─────────────────────────────────────────────
# 7. COLD-START FALLBACK
#    Used only when NO interactions exist anywhere in the system.
#    Ranks by eco_score derived from energy/water/certification data.
# ─────────────────────────────────────────────
def popularity_fallback(products: pd.DataFrame, top_n: int = 12) -> list:
    """Ranked by eco_score — for when the system has zero interactions."""
    df = products.copy()
    df["hybrid_score"] = df["eco_score"] / 100.0
    return df.sort_values("hybrid_score", ascending=False).head(top_n).to_dict(orient="records")


# ─────────────────────────────────────────────
# 8. PERSIST TO recommendation TABLE
#    Columns: recommendationID (PK auto_increment), userID, productID,
#             recommendation_score, reason, created_at
# ─────────────────────────────────────────────
def save_recommendations(engine, user_id: int, recs: list):
    with engine.connect() as conn:
        # Verify user exists — prevents FK constraint crash
        exists = conn.execute(
            text("SELECT COUNT(*) FROM user WHERE userID = :uid"),
            {"uid": user_id}
        ).scalar()

        if not exists:
            return  # Skip save — recs still returned to PHP

        conn.execute(
            text("DELETE FROM recommendation WHERE userID = :uid"),
            {"uid": user_id}
        )
        for rec in recs:
            conn.execute(
                text("""
                    INSERT INTO recommendation (userID, productID, recommendation_score)
                    VALUES (:uid, :pid, :score)
                """),
                {
                    "uid":   user_id,
                    "pid":   int(rec["productID"]),
                    "score": round(float(rec.get("hybrid_score", 0)) * 100, 2),
                },
            )
        conn.commit()


# ─────────────────────────────────────────────
# 9. ACCURACY EVALUATION
#    Leave-one-out method across all eligible users.
#
#    Formula 3.3: Accuracy  = (TP+TN) / (TP+FP+TN+FN)
#    Formula 3.4: Precision = TP / (TP+FP)
#    Formula 3.5: Recall    = TP / (TP+FN)
#    Formula 3.6: F1-Score  = 2 × Precision × Recall / (Precision + Recall)
# ─────────────────────────────────────────────
def evaluate_accuracy(
    engine,
    top_n: int = 10,
    alpha: float = 0.5,
    min_interactions: int = 3,
) -> dict:
    """
    Leave-one-out evaluation:
    For each eligible user:
      1. Hide one interaction (test item)
      2. Run recommender on remaining interactions
      3. Check if hidden item appears in top-N recommendations
      4. Compute TP, FP, FN, TN across all users
    """
    products     = load_products(engine)
    interactions = load_interactions(engine)

    # Only test users with enough interactions to leave one out
    user_counts = interactions.groupby("user_id")["product_id"].count()
    eligible    = user_counts[user_counts >= min_interactions].index.tolist()

    if not eligible:
        print("No eligible users for evaluation (need at least "
              f"{min_interactions} interactions per user).")
        return {}

    total_products = len(products)
    TP = FP = FN = TN = 0

    print(f"\nEvaluating {len(eligible)} users (top_n={top_n}, alpha={alpha})...")

    for user_id in eligible:
        user_interactions = interactions[interactions["user_id"] == user_id].copy()

        # Pick one interaction to hide (test item)
        test_row = user_interactions.sample(n=1, random_state=42)
        test_pid = int(test_row["product_id"].values[0])

        # Training set = all interactions except the hidden one
        train_interactions = interactions[
            ~((interactions["user_id"] == user_id) &
              (interactions["product_id"] == test_pid))
        ].copy()

        # Run recommender with training interactions
        try:
            recs = hybrid_recommend(
                user_id, products, train_interactions,
                top_n=top_n, alpha=alpha
            )
        except Exception as e:
            print(f"  Skipping user {user_id}: {e}")
            continue

        recommended_ids  = {int(r["productID"]) for r in recs}
        actual_positives = set(
            interactions[interactions["user_id"] == user_id]["product_id"].tolist()
        )

        # TP: hidden item was correctly recommended
        if test_pid in recommended_ids:
            TP += 1
        else:
            FN += 1

        # FP: recommended items user never interacted with
        FP += len(recommended_ids - actual_positives)

        # TN: items correctly not recommended (not relevant + not recommended)
        not_relevant     = total_products - len(actual_positives)
        not_recommended  = not_relevant - len(recommended_ids - actual_positives)
        TN += max(0, not_recommended)

    # ── Formulas 3.3 – 3.6 ──
    accuracy  = (TP + TN) / (TP + FP + TN + FN + 1e-9)
    precision = TP / (TP + FP + 1e-9)
    recall    = TP / (TP + FN + 1e-9)
    f1        = 2 * precision * recall / (precision + recall + 1e-9)

    results = {
        "users_tested":  len(eligible),
        "top_n":         top_n,
        "alpha":         alpha,
        "TP": TP, "FP": FP, "FN": FN, "TN": TN,
        "accuracy":  round(accuracy,  4),
        "precision": round(precision, 4),
         "recall":    round(recall,    4),
        "f1_score":  round(f1,        4),
    }

    print("\n══════════════════════════════════════")
    print("    GREENCHOICE ACCURACY EVALUATION   ")
    print("══════════════════════════════════════")
    print(f"  Users tested      : {results['users_tested']}")
    print(f"  Top-N             : {top_n}")
    print(f"  Alpha (CF weight) : {alpha}")
    print(f"  Min interactions  : {min_interactions}")
    print("──────────────────────────────────────")
    print(f"  TP = {TP}   FP = {FP}   FN = {FN}   TN = {TN}")
    print("──────────────────────────────────────")
    print(f"  Accuracy  (3.3) : {results['accuracy']}")
    print(f"  Precision (3.4) : {results['precision']}")
    print(f"  Recall    (3.5) : {results['recall']}")
    print(f"  F1-Score  (3.6) : {results['f1_score']}")
    print("══════════════════════════════════════\n")

    return results


# ─────────────────────────────────────────────
# 10. CLI ENTRY POINT
# ─────────────────────────────────────────────
def main():
    parser = argparse.ArgumentParser(description="GreenChoice Hybrid Recommender")
    parser.add_argument("user_id",     type=int,            help="Target user ID (use 0 with --evaluate)")
    parser.add_argument("--top_n",     type=int,   default=12,  help="Number of recommendations")
    parser.add_argument("--alpha",     type=float, default=0.5, help="CF weight (0=CBF, 1=CF, 0.5=hybrid)")
    parser.add_argument("--save",      action="store_true",     help="Save results to recommendation table")
    parser.add_argument("--evaluate",  action="store_true",     help="Run accuracy evaluation (Formulas 3.3-3.6)")
    parser.add_argument("--min_inter", type=int,   default=3,   help="Min interactions per user for evaluation")
    args = parser.parse_args()

    engine = get_engine()

    # ── Accuracy evaluation mode ──
    if args.evaluate:
        evaluate_accuracy(
            engine,
            top_n=args.top_n,
            alpha=args.alpha,
            min_interactions=args.min_inter,
        )
        return

    # ── Recommendation mode ──
    products     = load_products(engine)
    interactions = load_interactions(engine)

    user_has_history   = not interactions[interactions["user_id"] == args.user_id].empty
    system_has_history = not interactions.empty

    if user_has_history or system_has_history:
        recs = hybrid_recommend(
            args.user_id, products, interactions, args.top_n, args.alpha
        )
    else:
        recs = popularity_fallback(products, args.top_n)

    if args.save:
        save_recommendations(engine, args.user_id, recs)

    print(json.dumps(recs, default=str))


if __name__ == "__main__":
    main()