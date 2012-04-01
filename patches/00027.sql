alter table act_type drop shortName;
alter table act_type drop genArtName;
alter table act_type add regexps text not null default '';
alter table act_type add prefixes text not null default '';
update act_type set prefixes = name;
