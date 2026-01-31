#
# Table structure for table 'pages'
#
CREATE TABLE pages (
    tx_llmstxt_description text,
    tx_llmstxt_summary text,
    tx_llmstxt_keywords varchar(255) DEFAULT '' NOT NULL,
    tx_llmstxt_exclude tinyint(1) unsigned DEFAULT '0' NOT NULL,
    tx_llmstxt_priority int(11) DEFAULT '0' NOT NULL
);
