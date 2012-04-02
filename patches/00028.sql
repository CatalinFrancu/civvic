alter table act_type change regexps regexps text not null default '' after artName;
alter table act_type change prefixes prefixes text not null default '' after regexps;
alter table act_type add sectionNames text not null default '' after prefixes;
