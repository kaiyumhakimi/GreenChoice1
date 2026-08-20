"""
accuracy_visualization.py
Run this after getting your evaluation results to generate charts.

Install required libraries first:
    pip install matplotlib seaborn numpy

Run:
    python accuracy_visualization.py
"""

import numpy as np
import matplotlib.pyplot as plt
import matplotlib.patches as mpatches
import seaborn as sns

# ─────────────────────────────────────────────
# YOUR EVALUATION RESULTS — paste your numbers here
# ─────────────────────────────────────────────
results = {
    "Pure CBF (α=0.0)": {
        "TP": 63,  "FP": 1437, "FN": 87,  "TN": 1211117,
        "accuracy":  0.9987,
        "precision": 0.0420,
        "recall":    0.4200,
        "f1":        0.0764,
    },
    "Hybrid (α=0.5)": {
        "TP": 121, "FP": 1379, "FN": 29,  "TN": 1211175,
        "accuracy":  0.9988,
        "precision": 0.0807,
        "recall":    0.8067,
        "f1":        0.1467,
    },
    "Pure CF (α=1.0)": {
        "TP": 136, "FP": 1364, "FN": 14,  "TN": 1211190,
        "accuracy":  0.9989,
        "precision": 0.0907,
        "recall":    0.9067,
        "f1":        0.1648,
    },
}

# Top-N tradeoff results (all at alpha=0.5)
topn_results = {
    "top_n":      [3,      5,      10    ],
    "precision":  [0.2156, 0.1453, 0.0807],
    "recall":     [0.6467, 0.7267, 0.8067],
    "f1":         [0.3233, 0.2422, 0.1467],
}

# ─────────────────────────────────────────────
# SETUP
# ─────────────────────────────────────────────
plt.rcParams.update({
    "font.family":       "sans-serif",
    "font.size":         11,
    "axes.spines.top":   False,
    "axes.spines.right": False,
    "figure.facecolor":  "white",
    "axes.facecolor":    "white",
})

COLORS = {
    "cbf":       "#378add",
    "hybrid":    "#1d9e75",
    "cf":        "#d85a30",
    "precision": "#378add",
    "recall":    "#1d9e75",
    "f1":        "#d85a30",
    "accuracy":  "#7f77dd",
    "tp":        "#b5d4f4",
    "fp":        "#f0997b",
    "fn":        "#f7c1c1",
    "tn":        "#c0dd97",
}

configs   = list(results.keys())
short     = ["CBF\n(α=0.0)", "Hybrid\n(α=0.5)", "CF\n(α=1.0)"]
col_list  = [COLORS["cbf"], COLORS["hybrid"], COLORS["cf"]]

fig = plt.figure(figsize=(16, 14))
fig.suptitle("GreenChoice — Recommender System Accuracy Evaluation",
             fontsize=15, fontweight="bold", y=0.98)

# ─────────────────────────────────────────────
# FIGURE 1 — Confusion Matrices (3 side by side)
# ─────────────────────────────────────────────
for idx, (name, d) in enumerate(results.items()):
    ax = fig.add_subplot(4, 3, idx + 1)
    matrix = np.array([
        [d["TP"], d["FN"]],
        [d["FP"], d["TN"]],
    ])
    cm_colors = np.array([
        [COLORS["tp"], COLORS["fn"]],
        [COLORS["fp"], COLORS["tn"]],
    ])
    labels = [["TP", "FN"], ["FP", "TN"]]

    for r in range(2):
        for c in range(2):
            ax.add_patch(plt.Rectangle(
                (c, 1 - r), 1, 1,
                color=cm_colors[r][c], ec="white", lw=2
            ))
            val = f"{matrix[r][c]:,}"
            ax.text(c + 0.5, 1.5 - r, val,
                    ha="center", va="center",
                    fontsize=12, fontweight="bold", color="#2c2c2a")
            ax.text(c + 0.5, 1.5 - r - 0.25, labels[r][c],
                    ha="center", va="center",
                    fontsize=9, color="#5f5e5a")

    ax.set_xlim(0, 2)
    ax.set_ylim(0, 2)
    ax.set_xticks([0.5, 1.5])
    ax.set_xticklabels(["Predicted\nRelevant", "Predicted\nNot Relevant"], fontsize=9)
    ax.set_yticks([0.5, 1.5])
    ax.set_yticklabels(["Actually\nNot Relevant", "Actually\nRelevant"], fontsize=9)
    ax.set_title(name, fontsize=10, fontweight="bold", pad=8)
    ax.tick_params(length=0)
    for spine in ax.spines.values():
        spine.set_visible(False)

# ─────────────────────────────────────────────
# FIGURE 2 — Metrics Comparison Bar Chart
# ─────────────────────────────────────────────
ax2 = fig.add_subplot(4, 3, (4, 6))

metrics     = ["Accuracy", "Precision", "Recall", "F1-Score"]
metric_keys = ["accuracy", "precision", "recall", "f1"]
x           = np.arange(len(metrics))
width       = 0.25

for i, (name, d) in enumerate(results.items()):
    vals = [d[k] for k in metric_keys]
    bars = ax2.bar(x + i * width, vals, width,
                   label=configs[i], color=col_list[i],
                   alpha=0.85, edgecolor="white", linewidth=0.5)
    for bar, val in zip(bars, vals):
        ax2.text(bar.get_x() + bar.get_width() / 2,
                 bar.get_height() + 0.01,
                 f"{val:.4f}", ha="center", va="bottom",
                 fontsize=7.5, color="#444441")

ax2.set_xticks(x + width)
ax2.set_xticklabels(metrics)
ax2.set_ylabel("Score (0–1)")
ax2.set_ylim(0, 1.1)
ax2.set_title("Metric Comparison — CBF vs Hybrid vs CF", fontsize=11, fontweight="bold")
ax2.legend(fontsize=9, frameon=False)
ax2.yaxis.grid(True, alpha=0.3, linestyle="--")
ax2.set_axisbelow(True)

# ─────────────────────────────────────────────
# FIGURE 3 — Precision, Recall, F1 bar (no accuracy)
# ─────────────────────────────────────────────
ax3 = fig.add_subplot(4, 3, (7, 9))

metrics2     = ["Precision", "Recall", "F1-Score"]
metric_keys2 = ["precision", "recall", "f1"]
x2           = np.arange(len(metrics2))

for i, (name, d) in enumerate(results.items()):
    vals = [d[k] for k in metric_keys2]
    bars = ax3.bar(x2 + i * width, vals, width,
                   label=configs[i], color=col_list[i],
                   alpha=0.85, edgecolor="white", linewidth=0.5)
    for bar, val in zip(bars, vals):
        ax3.text(bar.get_x() + bar.get_width() / 2,
                 bar.get_height() + 0.005,
                 f"{val:.4f}", ha="center", va="bottom",
                 fontsize=8, color="#444441")

ax3.set_xticks(x2 + width)
ax3.set_xticklabels(metrics2)
ax3.set_ylabel("Score (0–1)")
ax3.set_ylim(0, 1.05)
ax3.set_title("Precision · Recall · F1 — Detail View", fontsize=11, fontweight="bold")
ax3.legend(fontsize=9, frameon=False)
ax3.yaxis.grid(True, alpha=0.3, linestyle="--")
ax3.set_axisbelow(True)

# ─────────────────────────────────────────────
# FIGURE 4 — Precision-Recall tradeoff at different top-N
# ─────────────────────────────────────────────
ax4 = fig.add_subplot(4, 3, (10, 11))

topn_labels = [f"top-{n}" for n in topn_results["top_n"]]
ax4.plot(topn_labels, topn_results["precision"],
         marker="o", color=COLORS["precision"], linewidth=2,
         label="Precision", markersize=7)
ax4.plot(topn_labels, topn_results["recall"],
         marker="s", color=COLORS["recall"], linewidth=2,
         label="Recall", markersize=7)
ax4.plot(topn_labels, topn_results["f1"],
         marker="^", color=COLORS["f1"], linewidth=2,
         label="F1-Score", markersize=7)

for i, n in enumerate(topn_labels):
    ax4.annotate(f"{topn_results['precision'][i]:.4f}",
                 (n, topn_results["precision"][i]),
                 textcoords="offset points", xytext=(0, 8),
                 ha="center", fontsize=8, color=COLORS["precision"])
    ax4.annotate(f"{topn_results['recall'][i]:.4f}",
                 (n, topn_results["recall"][i]),
                 textcoords="offset points", xytext=(0, 8),
                 ha="center", fontsize=8, color=COLORS["recall"])
    ax4.annotate(f"{topn_results['f1'][i]:.4f}",
                 (n, topn_results["f1"][i]),
                 textcoords="offset points", xytext=(0, -14),
                 ha="center", fontsize=8, color=COLORS["f1"])

ax4.set_ylabel("Score (0–1)")
ax4.set_ylim(0, 1.0)
ax4.set_title("Precision–Recall Tradeoff at Different Top-N\n(α=0.5, Hybrid)", fontsize=11, fontweight="bold")
ax4.legend(fontsize=9, frameon=False)
ax4.yaxis.grid(True, alpha=0.3, linestyle="--")
ax4.set_axisbelow(True)

# ─────────────────────────────────────────────
# FIGURE 5 — Summary table
# ─────────────────────────────────────────────
ax5 = fig.add_subplot(4, 3, 12)
ax5.axis("off")

table_data = [
    ["Configuration", "Accuracy", "Precision", "Recall", "F1-Score"],
]
for name, d in results.items():
    table_data.append([
        name,
        f"{d['accuracy']:.4f}",
        f"{d['precision']:.4f}",
        f"{d['recall']:.4f}",
        f"{d['f1']:.4f}",
    ])

table = ax5.table(
    cellText=table_data[1:],
    colLabels=table_data[0],
    loc="center",
    cellLoc="center",
)
table.auto_set_font_size(False)
table.set_fontsize(8.5)
table.scale(1.2, 1.6)

# Style header
for j in range(5):
    table[0, j].set_facecolor("#265138")
    table[0, j].set_text_props(color="white", fontweight="bold")

# Style rows
row_colors = ["#e1f5ee", "#ffffff", "#e1f5ee"]
for i in range(1, 4):
    for j in range(5):
        table[i, j].set_facecolor(row_colors[i - 1])

# Highlight hybrid row
for j in range(5):
    table[2, j].set_facecolor("#9fe1cb")
    table[2, j].set_text_props(fontweight="bold")

ax5.set_title("Summary Table", fontsize=11, fontweight="bold", pad=10)

# ─────────────────────────────────────────────
# SAVE + SHOW
# ─────────────────────────────────────────────
plt.tight_layout(rect=[0, 0, 1, 0.97])
plt.savefig("greenchoice_accuracy_results.png", dpi=150, bbox_inches="tight")
print("Saved: greenchoice_accuracy_results.png")
plt.show()