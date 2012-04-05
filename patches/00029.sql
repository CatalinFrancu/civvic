alter table act_author add signatureType int not null after authorId;
update act_author set signatureType = 1;
alter table act_author add note text not null after signatureType;
