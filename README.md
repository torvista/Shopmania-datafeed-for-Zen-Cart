# Shopmania-datafeed-for-Zen-Cart
Based on Shopmania datafeed for Zen Cart v1.21

This code is offered to help others who want to keep using this with php7.

At start of script, set debug=true to limit the results and show more info for debugging purposes.

Change Log
1) Obsolete functions changed (mysql and eregi) for php7.
2) SQL.
1.21 used the first occurring product-category id instead of the master_category id - corrected.
1.21 did not filter on products marked as call-for-price or price=0 but then complained about it on the feed analysis in the Shopmania dashboard: corrected. 
3) Added filters to the product description cleaning function to remove youtube videos, images and custom items embedded in the description.
4) Added a lot of echos scattered about for debugging purposes.

TODO...for someone else.
Since it uses application top, it should use Zen Cart native $db class instead of a direct connection.
