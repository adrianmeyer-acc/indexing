select 
  bcf.bug_id as ticket_id,
  DATE_FORMAT( FROM_UNIXTIME( b.date_submitted ), '%d.%m.%Y %H:%i' ) as created,  
  ( select bp.name from mantis.`mantis_project_table` bp where bp.id=b.project_id ) as project,
  b.category_id,
  ( select bc.name from mantis.`mantis_category_table` bc where bc.id=b.category_id ) as category,
  ( select bu.realname from mantis.`mantis_user_table` bu where bu.id=b.reporter_id ) as reporter,
  ( select left( SUBSTRING( statuses, INSTR( statuses, status ) + length(status) + 1 ), LOCATE( ',', SUBSTRING( statuses, INSTR( statuses, status ) + length(status) + 1 ))-1 )
    from ( select concat( replace( value, '"', ',' ), ',' ) as statuses from mantis.`mantis_config_table` ct where ct.config_id = 'status_enum_workflow' and ct.project_id=0 ) a ) as status,
  summary,
  ( select group_concat( tt.name ) 
	from mantis.mantis_bug_tag_table btt, mantis.mantis_tag_table tt
	where btt.bug_id=0 and btt.tag_id=bcf.bug_id ) as tags,
  c.customer_id,
  ( select company from serviceChampion11.customer_default_data cda, serviceChampion11.address a
    where cda.address_id = a.address_id and cda.customer_id = c.customer_id limit 1 ) as customer
from 
  serviceChampion11.`customer` c,
  mantis.`mantis_custom_field_string_table` bcf,
  mantis.`mantis_bug_table` b
where project_id in ( 25,26,27,28,33,159,
	139, -- aagena Intern & Service Champion
	54,  -- bpm-sports Interna
	185, -- Emch Service Champion
	69,  -- EOP Intern & Service Champion
	170, -- Hapa Service Champion
	87,  -- Helppoint Service Champion
	165, -- Laetus Service Champion
	181, -- MEDSEEK Service Champion
	46,  -- Swisscom BS Intern & Service Champion
	186, -- Swisscom SmartLife Service Champion
	155  -- viceversa Intern & Service Champion
   )
and bcf.field_id=2
and bcf.value=c.customer_id
and bcf.bug_id=b.id
order by 2 desc;