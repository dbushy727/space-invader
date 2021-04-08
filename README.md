# Spaceinvader Bot
This bot looks at the space invader shop site and monitors for available products to purchase.

## Two Methods
This bot is utilizing two methods of crawling for different needs. 

### Method 1
This method checks product pages it already knows, and checks if their status changes to purchasable. This method utilizes Laravel Zero to simply fetch the site, and check the body for specific strings like "AVAILABLE SOON" or "SOLD OUT".

This method also does a very dumb brute force crawl where it checks for product pages that exist with ids 1-100.

Relevant code is in `app/Commands/PollSpaceInvader.php`

### Method 2
This method utilizes cypress to actually spin up a headless browser and visit the site. It will then find the "next product" button, and look for pages it hasn't seen before. If it finds a new page, it sends out a notification via Discord, and adds it to its simple database of known pages. 

Relevant code is in `cypress/integration/crawler/pollForNewProducts.spec.js`
