alter table act_version drop issueDate;
alter table act_version add monitorId int default null after status;
update act_version set monitorId = 120 where id = 960;
