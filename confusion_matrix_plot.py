"""
confusion_matrix_plot.py
Generates the confusion matrix graph for Table 5.1.

Run:
    python confusion_matrix_plot.py

Output:
    confusion_matrix.png  (saved in same folder)
"""

import numpy as np
import matplotlib.pyplot as plt
import matplotlib.patches as mpatches
from matplotlib.colors import LinearSegmentedColormap

# ─────────────────────────────────────────────
# YOUR DATA FROM TABLE 5.1
# ─────────────────────────────────────────────
configs = {
    "Pure CBF\n(α=0.0)": {
        "TP": 55,   "FP": 1455,
        "FN": 96,   "TN": 1218927,
    },
    "Hybrid System\n(α=0.5)": {
        "TP": 121,  "FP": 1379,
        "FN": 29,   "TN": 1211175,
    },
    "Pure CF\n(α=1.0)": {
        "TP": 103,  "FP": 1407,
        "FN": 48,   "TN": 1218975,
    },
}

# ─────────────────────────────────────────────
# PLOT — one confusion matrix per configuration
# ─────────────────────────────────────────────
fig, axes = plt.subplots(1, 3, figsize=(14, 4.5))
fig.suptitle(
    "Figure 5.X  Confusion Matrix Comparison (Top-N = 10)",
    fontsize=13, fontweight="bold", y=1.02
)

# Row/column labels (standard confusion matrix layout)
row_labels = ["Actually\nRelevant", "Actually\nNot Relevant"]
col_labels  = ["Predicted\nRelevant", "Predicted\nNot Relevant"]

# Custom colour map: deep blue (large TN) → white → deep green (TP)
cmap_pos = LinearSegmentedColormap.from_list(
    "gc", ["#ffffff", "#1d9e75"], N=256
)
cmap_neg = LinearSegmentedColormap.from_list(
    "rc", ["#ffffff", "#d85a30"], N=256
)

for ax, (title, d) in zip(axes, configs.items()):
    TP, FP, FN, TN = d["TP"], d["FP"], d["FN"], d["TN"]

    # 2×2 matrix layout:
    #   [TP  FN]
    #   [FP  TN]
    matrix = np.array([[TP, FN], [FP, TN]])

    # Normalise each cell independently for colour intensity
    # (log scale so TN doesn't dwarf everything)
    log_m = np.log1p(matrix).astype(float)

    # Cell background colours
    cell_colors = [
        ["#b5d4f4", "#f7c1c1"],   # TP=blue, FN=red
        ["#f0997b", "#c0dd97"],   # FP=orange, TN=green
    ]
    cell_text_colors = [
        ["#0c447c", "#791f1f"],
        ["#712b13", "#27500a"],
    ]
    cell_labels = [
        ["TP", "FN"],
        ["FP", "TN"],
    ]

    for r in range(2):
        for c in range(2):
            val   = matrix[r, c]
            color = cell_colors[r][c]
            tc    = cell_text_colors[r][c]
            label = cell_labels[r][c]

            ax.add_patch(plt.Rectangle(
                (c, 1 - r), 1, 1,
                color=color, ec="white", lw=2
            ))

            # Label (TP / FP / FN / TN)
            ax.text(
                c + 0.5, 1.5 - r + 0.18,
                label,
                ha="center", va="center",
                fontsize=10, fontweight="bold",
                color=tc, alpha=0.6
            )

            # Value
            ax.text(
                c + 0.5, 1.5 - r - 0.08,
                f"{val:,}",
                ha="center", va="center",
                fontsize=13, fontweight="bold",
                color=tc
            )

    ax.set_xlim(0, 2)
    ax.set_ylim(0, 2)
    ax.set_xticks([0.5, 1.5])
    ax.set_xticklabels(col_labels, fontsize=9)
    ax.set_yticks([0.5, 1.5])
    ax.set_yticklabels(["Actually\nNot Relevant", "Actually\nRelevant"],
                       fontsize=9)
    ax.tick_params(length=0)
    ax.set_title(title, fontsize=11, fontweight="bold", pad=10)
    for spine in ax.spines.values():
        spine.set_visible(False)

# ── Legend ──
legend_patches = [
    mpatches.Patch(color="#b5d4f4", label="TP — correctly recommended"),
    mpatches.Patch(color="#f7c1c1", label="FN — relevant item missed"),
    mpatches.Patch(color="#f0997b", label="FP — irrelevant item recommended"),
    mpatches.Patch(color="#c0dd97", label="TN — correctly not recommended"),
]
fig.legend(
    handles=legend_patches,
    loc="lower center",
    ncol=4,
    fontsize=8.5,
    frameon=False,
    bbox_to_anchor=(0.5, -0.08)
)

plt.tight_layout()
plt.savefig("confusion_matrix.png", dpi=150, bbox_inches="tight")
print("Saved: confusion_matrix.png")
plt.show()