const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());

async function scrapeProduct(searchQuery, minPrice = 0, maxPrice = Infinity) {
    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-blink-features=AutomationControlled']
    });
    const page = await browser.newPage();
    await page.setUserAgent("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36");

    let results = { amazon: [], snapdeal: [] };

    try {
        // ✅ Amazon Scraper
        const amazonUrl = `https://www.amazon.in/s?k=${encodeURIComponent(searchQuery)}`;
        await page.goto(amazonUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });

        try {
            await page.waitForSelector('div.s-main-slot div[data-component-type="s-search-result"]', { timeout: 10000 });
            results.amazon = await page.evaluate((minPrice, maxPrice) =>
                Array.from(document.querySelectorAll('div.s-main-slot div[data-component-type="s-search-result"]'))
                    .map(item => {
                        let priceText = item.querySelector('span.a-price > span.a-offscreen')?.innerText.replace(/[₹,]/g, "").trim() || "0";
                        let originalPriceText = item.querySelector('span.a-price.a-text-price > span.a-offscreen')?.innerText.replace(/[₹,]/g, "").trim() || "0";

                        let price = parseFloat(priceText);
                        let originalPrice = parseFloat(originalPriceText);
                        let discountPercentage = (originalPrice > 0 && price > 0) ? Math.round(((originalPrice - price) / originalPrice) * 100) : 0;

                        return {
                            title: item.querySelector('h2 span')?.innerText.trim() || "Title Not Found",
                            original_price: originalPrice > 0 ? `₹${originalPrice}` : "N/A",
                            price: price > 0 ? `₹${price}` : "N/A",
                            discount: discountPercentage > 0 ? `${discountPercentage}% Off` : "No Discount",
                            link: item.querySelector('h2 a') ? 'https://www.amazon.in' + item.querySelector('h2 a').getAttribute('href') : ""
                        };
                    })
                    .filter(product => {
                        let productPrice = parseFloat(product.price.replace(/₹/g, "").trim()) || 0;
                        return productPrice >= minPrice && productPrice <= maxPrice;
                    })
                    .slice(0, 8)
            , minPrice, maxPrice);
        } catch (err) {
            console.log("❌ Amazon error:", err.message);
        }

        // ✅ Snapdeal Scraper
        const snapdealUrl = `https://www.snapdeal.com/search?keyword=${encodeURIComponent(searchQuery)}`;
        await page.goto(snapdealUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });

        try {
            await page.waitForSelector('div.product-tuple-listing', { timeout: 10000 });
            results.snapdeal = await page.evaluate(() =>
                Array.from(document.querySelectorAll('div.product-tuple-listing'))
                    .map(item => {
                        let priceText = item.querySelector('span.product-price')?.innerText.replace(/[^0-9]/g, "").trim() || "0";
                        let originalPriceText = item.querySelector('span.product-desc-price')?.innerText.replace(/[^0-9]/g, "").trim() || "0";
                        
                        let price = parseFloat(priceText);
                        let originalPrice = parseFloat(originalPriceText);
                        let discount = originalPrice > 0 ? Math.round(((originalPrice - price) / originalPrice) * 100) : 0;

                        return {
                            title: item.querySelector('p.product-title')?.innerText.trim() || "Title Not Found",
                            original_price: originalPrice > 0 ? `₹${originalPrice}` : "N/A",
                            price: price > 0 ? `₹${price}` : "N/A",
                            discount: discount > 0 ? `${discount}% Off` : "No Discount",
                            link: item.querySelector('a.dp-widget-link')?.href || ""
                        };
                    })
                    .filter(product => product.price !== "N/A")
                    .slice(0, 8)
            );
        } catch (err) {
            console.log("❌ Snapdeal error:", err.message);
        }

    } finally {
        await browser.close();
    }
    return results;
}

// Get search query and price filters from command line arguments
const args = process.argv.slice(2);
const searchQuery = args[0] || "";
const minPrice = args[1] ? parseFloat(args[1]) : 0;
const maxPrice = args[2] ? parseFloat(args[2]) : Infinity;

if (!searchQuery) {
    console.log("⚠️ Please provide a search term. Example: node amazon_flipkart_scraper.js 'iPhone 15' 40000 70000");
} else {
    scrapeProduct(searchQuery, minPrice, maxPrice).then(data => console.log(JSON.stringify(data, null, 2)));
}
