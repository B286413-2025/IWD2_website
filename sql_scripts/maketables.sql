# Adapted from class code
DROP DATABASE IF EXISTS s2883992_web_project;
CREATE DATABASE s2883992_web_project;
USE s2883992_web_project;

# queries table for unique query combinations (protein and taxon)
CREATE TABLE `queries` (
`query_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`protein_family` VARCHAR(255) NOT NULL,
`taxon` VARCHAR(255) NOT NULL,
`query_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (query_id),
CONSTRAINT `full_query` UNIQUE (`protein_family`, `taxon`), #unique constraint for protein-taxon combination
# TODO: Think a bit more about indexing
INDEX `idx_taxon` (`taxon`), 
INDEX `idx_protein` (`protein_family`)
);

# sequences table for storing unique sequences once
CREATE TABLE `sequences` (
`accession` VARCHAR(255) NOT NULL,
`organism` VARCHAR(255) NOT NULL, 
`sequence` LONGTEXT NOT NULL, 
PRIMARY KEY(`accession`)
); 
 
# Mapping table for queries and sequences
CREATE TABLE `seq_group` (
`query_id` INT UNSIGNED NOT NULL,
`accession` VARCHAR(255) NOT NULL,
PRIMARY KEY (`query_id`, `accession`), # Unique combinations of query and sequence, for sequences in multiple queries
FOREIGN KEY (`query_id`) REFERENCES `queries`(`query_id`) ON DELETE CASCADE ON UPDATE CASCADE, # link to queries table
FOREIGN KEY (`accession`) REFERENCES `sequences`(`accession`) ON DELETE CASCADE ON UPDATE CASCADE # link to sequences table
);

# Table for connecting users (browser, cookies) and queries
CREATE TABLE `jobs` (
`job_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`user_hash` CHAR(64) NOT NULL, # Hash of cookie token
`query_id` INT UNSIGNED NOT NULL,
`is_example` TINYINT(1) NOT NULL DEFAULT 0,
`job_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
`status` ENUM('pending', 'complete', 'error') DEFAULT 'pending',
PRIMARY KEY (`job_id`),
FOREIGN KEY (`query_id`) REFERENCES `queries`(`query_id`) ON DELETE CASCADE ON UPDATE CASCADE,
INDEX (`user_hash`),
INDEX (`query_id`)
);

# Table for analysis output
CREATE TABLE `analysis_outputs` (
`output_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`job_id` INT UNSIGNED NOT NULL,
`analysis_type` ENUM('msa', 'plotcon', 'motifs') NOT NULL, # Maybe VARCHAR for flexibility
`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
`mime_type` VARCHAR(100) NULL,
`file_name` VARCHAR(255) NULL,
`parameters` TEXT NULL,
# `file_path` VARCHAR(255) NULL, # Only if I find a secure way to save files
`text_data` LONGTEXT NULL,
`blob_data` LONGBLOB NULL,
PRIMARY KEY (`output_id`),
FOREIGN KEY (`job_id`) REFERENCES `jobs`(`job_id`) ON DELETE CASCADE ON UPDATE CASCADE
);

# Table for alignment results
CREATE TABLE `aligned_sequences` (
`job_id` INT UNSIGNED NOT NULL,
`accession` VARCHAR(255) NOT NULL,
`aligned_sequence` LONGTEXT NOT NULL,
PRIMARY KEY (`job_id`, `accession`),
FOREIGN KEY (`job_id`) REFERENCES jobs(`job_id`) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (`accession`) REFERENCES sequences(`accession`) ON DELETE CASCADE ON UPDATE CASCADE
);

# Table for motifs
CREATE TABLE `motif_hits` (
`hit_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`job_id` INT UNSIGNED NOT NULL,
`accession` VARCHAR(255) NOT NULL,
`motif_name` VARCHAR(255) NOT NULL,
`start_pos` INT UNSIGNED NOT NULL,
`end_pos` INT UNSIGNED NOT NULL,
`score` DOUBLE NULL,
`matched_sequence` VARCHAR(255) NULL,
`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`hit_id`),
FOREIGN KEY (`job_id`) REFERENCES `jobs`(`job_id`) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (`accession`) REFERENCES `sequences`(`accession`) ON DELETE CASCADE ON UPDATE CASCADE,
INDEX (`job_id`),
INDEX (`accession`)
);

