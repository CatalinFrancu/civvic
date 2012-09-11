alter table act_type add hasNumbers int not null after artName;
update act_type set hasNumbers = 1;
alter table act_reference change number number varchar(255), change year year int;
