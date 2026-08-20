<div class="product-grid">
    {% for product in products %}
    <div class="product-card">
        <img src="{{ product.Image_URL }}" alt="{{ product.Product_Name }}">
        <h3>{{ product.Product_Name }}</h3>
        <p>Brand: {{ product.Brand }}</p>
        
        <div class="score-container">
            <small>Match Score: {{ (product.final_score * 100)|round|int }}%</small>
            <div style="background: #eee; width: 100%; height: 10px;">
                <div style="background: #4caf50; width: {{ product.final_score * 100 }}%; height: 100%;"></div>
            </div>
        </div>

        <button>View Details</button>
    </div>
    {% endfor %}
</div>