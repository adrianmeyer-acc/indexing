-- SQL used to generate JSON:
-- contacts that are on an active customer
-- only email address if it is available
SELECT 
  cu.customer_id,
  co.contact_id,
  co.firstname,
  co.lastname,
  co.gender,  
  ( select  ccd.contact_data from serviceChampion11.`contact_contact_data` ccd where  ccd.contact_data_type_id = 10 and ccd.contact_id = co.contact_id
    order by ( select count(*) from serviceChampion11.`contact_default_data` cdd where cdd.contact_contact_data_id = ccd.contact_contact_data_id ) desc limit 1 ) as email,
  ( select  ccd.contact_data from serviceChampion11.`contact_contact_data` ccd where  ccd.contact_data_type_id = 3 and ccd.contact_id = co.contact_id
    order by ( select count(*) from serviceChampion11.`contact_default_data` cdd where cdd.contact_contact_data_id = ccd.contact_contact_data_id ) desc limit 1 ) as mobile,
  ( select  ccd.contact_data from serviceChampion11.`contact_contact_data` ccd where  ccd.contact_data_type_id = 1 and ccd.contact_id = co.contact_id
    order by ( select count(*) from serviceChampion11.`contact_default_data` cdd where cdd.contact_contact_data_id = ccd.contact_contact_data_id ) desc limit 1 ) as office,
  adr.company,
  adr.street,
  adr.building_nr,
  adr.city,
  adr.zip,
  adr.country_code
FROM 
  serviceChampion11.`customer` cu,
  serviceChampion11.`customer-address` cadr,
  serviceChampion11.`address-contact` adrc,
  serviceChampion11.`address` adr,
  serviceChampion11.`contact` co
WHERE cu.status_id = 1 -- only active customers
AND cadr.customer_id = cu.customer_id
AND cadr.address_id = adrc.address_id
AND cadr.address_id = adr.address_id
AND adrc.contact_id = co.contact_id
AND ( co.firstname <> '' OR co.lastname <> '' );