import traceback
from flask import Flask, request, jsonify
from recommender import (
    get_engine, load_products, load_interactions,
    hybrid_recommend, popularity_fallback, save_recommendations
)

app = Flask(__name__)


@app.route('/recommend', methods=['GET'])
def recommend():
    user_id = request.args.get('user_id', type=int)
    top_n   = request.args.get('top_n',   type=int,   default=12)
    alpha   = request.args.get('alpha',   type=float, default=0.5)
    save    = request.args.get('save',    type=int,   default=0)

    if user_id is None:
        return jsonify({"error": "user_id is required"}), 400

    try:
        engine       = get_engine()
        products     = load_products(engine)
        interactions = load_interactions(engine)

        user_has_history   = not interactions[interactions["user_id"] == user_id].empty
        system_has_history = not interactions.empty

        if user_has_history or system_has_history:
            recs = hybrid_recommend(user_id, products, interactions, top_n, alpha)
        else:
            recs = popularity_fallback(products, top_n)

        if save and recs:
            save_recommendations(engine, user_id, recs)

        return jsonify({"user_id": user_id, "recommendations": recs})

    except Exception as e:
        # Print full traceback to terminal so you can see exactly what crashed
        traceback.print_exc()
        return jsonify({"error": str(e)}), 500


@app.route('/health', methods=['GET'])
def health():
    return jsonify({"status": "ok"})


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=False)