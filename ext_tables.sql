CREATE TABLE tx_cfcleague_games (
	newspreview2 int(11) DEFAULT '0' NOT NULL,
	newsreport2 int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_cfcleague_club (
	feusers int(11) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'tx_cfcleague_club2feusers_mm'
# uid_local used for club
#
CREATE TABLE tx_cfcleague_club2feusers_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	tablenames varchar(50) DEFAULT '' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);


CREATE TABLE tx_cfcleague_match_notes (
	crfeuser int(11) DEFAULT '0' NOT NULL
);
