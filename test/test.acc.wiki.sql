select 
  11 as wiki_id,
  a.page_id,
  a.page_title,
  a.page_counter,
  a.page_len,
  ( select cast( rev_timestamp as char ) from wiki11de.revision r where r.rev_id = a.rev_id ) as last_change,
  ( select u.user_real_name from wiki11de.revision r, wiki11de.user u where u.user_id = r.rev_user and r.rev_id = a.rev_id ) as user
from (
	select 
	  p.`page_id`,
	  p.`page_title`,
	  p.`page_counter`,
	  p.`page_len`,
	  ( select max(rev_id) as rev_id from wiki11de.revision r 
		where r.rev_page = p.page_id and rev_user <> 0 ) rev_id
	from 
	  wiki11de.`page` p
	where p.`page_is_redirect` = 0
	and p.`page_len` > 0 ) a
union all
select 
  0 as wiki_id,
  a.page_id,
  a.page_title,
  a.page_counter,
  a.page_len,
  ( select cast( rev_timestamp as char ) from wiki11de.revision r where r.rev_id = a.rev_id ) as last_change,
  ( select u.user_real_name from wiki11de.revision r, wiki11de.user u where u.user_id = r.rev_user and r.rev_id = a.rev_id ) as user
from (
	select 
	  p.`page_id`,
	  p.`page_title`,
	  p.`page_counter`,
	  p.`page_len`,
	  ( select max(rev_id) as rev_id from wiki11de.revision r 
		where r.rev_page = p.page_id and rev_user <> 0 ) rev_id
	from 
	  wiki0de.`page` p
	where p.`page_is_redirect` = 0
	and p.`page_len` > 0 ) a;