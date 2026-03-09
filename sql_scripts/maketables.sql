# Adapted from class code
CREATE DATABASE IF NOT EXISTS s2883992_web_project;
USE s2883992_web_project;
#DROP TABLE seq_group;
#DROP TABLE sequences;
#DROP TABLE jobs;
#DROP TABLE queries;

# queries table for unique query combinations (protein and taxon)
CREATE TABLE `queries` (
`query_id` INT UNSIGNED NOT NULL AUTO_INCREMENT KEY,
`protein_family` VARCHAR(255) NOT NULL,
`taxon` VARCHAR(255) NOT NULL,
`query_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
`is_example` TINYINT(1) NOT NULL DEFAULT 0, # for example set retrieval
CONSTRAINT `full_query` UNIQUE (`protein_family`, `taxon`) # unique constraint for protein-taxon combination
);

# sequences table for storing unique sequences once
CREATE TABLE `sequences` (
`accession` VARCHAR(255) NOT NULL,
`organism` VARCHAR(255) NOT NULL, 
`sequence` LONGTEXT NOT NULL, 
PRIMARY KEY(`accession`)
); 
 
# mapping table for queries and sequences
CREATE TABLE `seq_group` (
`query_id` INT UNSIGNED NOT NULL,
`accession` VARCHAR(255) NOT NULL,
#`clustalo_seq` LONGTEXT NOT NULL, 
PRIMARY KEY (`query_id`, `accession`), # unique combinations of query and sequence, for sequences in multiple queries
FOREIGN KEY (`query_id`) REFERENCES `queries`(`query_id`) ON DELETE CASCADE, # link to queries table
FOREIGN KEY (`accession`) REFERENCES `sequences`(`accession`) ON DELETE CASCADE # link to sequences table
);

# table for connecting users and queries
CREATE TABLE `jobs` (
`job_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`user_id` VARCHAR(255) NOT NULL,
`query_id` INT UNSIGNED NOT NULL,
`job_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
`status` ENUM('pending', 'complete', 'error') DEFAULT 'pending',
PRIMARY KEY (`job_id`),
FOREIGN KEY (`query_id`) REFERENCES `queries`(`query_id`) ON DELETE CASCADE
);

# table for analysis output
CREATE TABLE `analysis_outputs` (
`output_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`job_id` INT UNSIGNED NOT NULL,
`analysis_type` ENUM('msa', 'plotcon', 'motifs') NOT NULL,
`file_path` VARCHAR(255) NOT NULL,
`parameters` TEXT NULL,
`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`output_id`),
FOREIGN KEY (`job_id`) REFERENCES `jobs`(`job_id`) ON DELETE CASCADE
);

# table for alignment results
CREATE TABLE `aligned_sequences` (
`align_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`job_id` INT UNSIGNED NOT NULL,
`accession` VARCHAR(255) NOT NULL,
`aligned_sequence` LONGTEXT NOT NULL,
PRIMARY KEY (`align_id`),
FOREIGN KEY (`job_id`) REFERENCES jobs(`job_id`) ON DELETE CASCADE,
FOREIGN KEY (`accession`) REFERENCES sequences(`accession`) ON DELETE CASCADE
);

# table for motifs
CREATE TABLE `motif_hits` (
`hit_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`job_id` INT UNSIGNED NOT NULL,
`accession` VARCHAR(255) NOT NULL,
`motif_name` VARCHAR(255) NOT NULL,
`start_pos` INT UNSIGNED NOT NULL,
`end_pos` INT UNSIGNED NOT NULL,
`score` INT NULL,
`strand` ENUM('+', '-') NOT NULL, # Maybe ignore this because we have protein seqs
`matched_sequence` VARCHAR(255) NULL,
`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`hit_id`),
    FOREIGN KEY (`job_id`) REFERENCES `jobs`(`job_id`) ON DELETE CASCADE,
    FOREIGN KEY (`accession`) REFERENCES `sequences`(`accession`) ON DELETE CASCADE,
    INDEX (`job_id`),
    INDEX (`accession`)
);


