SELECT `s`.`id`, COUNT(DISTINCT(spa.product_item_id)) as products, 
GROUP_CONCAT(DISTINCT(sd.name) SEPARATOR ", ") as delivery, 
GROUP_CONCAT(DISTINCT(sp.name) SEPARATOR ", ") as payment, 
COUNT(DISTINCT(sdoc.id)) as documents, 
GROUP_CONCAT(DISTINCT(ca.name) SEPARATOR ", ") as shop_city, 
`c`.`name` AS `shop_city_name` 
FROM `shop` `s` 
INNER JOIN `city` `c` ON c.id = s.city_id 
LEFT JOIN `shop_product_assignment` `spa` ON spa.shop_id=s.id AND spa.price > 0 
LEFT JOIN `shop_delivery` `sd` ON sd.shop_id=s.id 
LEFT JOIN `shop_payment` `sp` ON sp.shop_id=s.id 
LEFT JOIN `shop_document` `sdoc` ON sdoc.shop_id = s.id 
LEFT JOIN `shop_address` `sa` ON sa.shop_id = s.id AND sa.city_id != s.city_id 
LEFT JOIN `city` `ca` ON ca.id = sa.city_id 
WHERE `s`.`id` IN (1624, 1295, 2081, 1480, 1907, 1448, 1832, 1964, 1669, 1377) 
GROUP BY `s`.`id`