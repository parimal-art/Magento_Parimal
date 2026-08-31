# Codilar SliderProduct Module

This Magento 2 module implements a dynamic "Related Products" slider that displays products from the same deepest subcategory as the currently viewed product. It utilizes the Magento Widget framework for easy placement and integrates the Swiper.js library for a responsive, touch-friendly carousel experience.

## What I Learned & Implemented

*   **Widget Configuration:** Created `etc/widget.xml` to register a custom widget (`Related Products Slider`) that can be placed via the Admin Panel (CMS Pages/Blocks).
*   **Custom Block Logic:** Built `Block/RelatedProduct.php` to fetch the current product's category hierarchy, identify the deepest subcategory, and retrieve related products excluding the current one.
*   **Collection Filtering:** Used `CollectionFactory` to efficiently query products based on category ID and entity ID filters.
*   **Frontend Integration:** Integrated **Swiper.js** (via CDN) in the template to create a modern, responsive slider with navigation arrows.
*   **Responsive Design:** Implemented CSS breakpoints in the JavaScript configuration to adjust the number of visible slides based on screen size (Mobile: 1, Tablet: 2, Desktop: 3).
*   **Data Escaping:** Ensured security by using `$escaper->escapeHtml()` and `$escaper->escapeUrl()` in the PHTML template.

## How It Works

1. **Widget Initialization:**  
   When the widget is rendered on a page, the `RelatedProduct` block is instantiated.

2. **Context Detection:**  
   The block checks whether the current page is a **Product View** page (`catalog_product_view`) and retrieves the **Product ID** from the request.

3. **Category Analysis:**  
   It fetches all categories assigned to the product and identifies the category with the highest `level`, which represents the **most specific subcategory**.

4. **Product Retrieval:**  
   It queries for other products that belong to the same **subcategory**, while excluding the current product.

5. **Rendering:**  
   The template loops through the product collection and generates slide items containing the **product image, name, and price**, then initializes the **Swiper.js carousel**.
## Visuals

![Magento 2 Custom Module Output](img.png)

