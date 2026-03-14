#!/usr/bin/python3

## Performing patmatmotifs on fasta sequences and generating a tsvfile for SQL loading

# Modules
import os, subprocess, sys, argparse

# Command line arguments
parser = argparse.ArgumentParser(
        prog='RunPatMatMotifs',
        description='Motifs searching with EMBOSS patmatmotifs and generating a tsv file for SQL loading')
parser.add_argument('in_tsv', action='store', help='Input TSV file: accession\torganism\tsequence')
parser.add_argument('out_sql', action='store', help="Outfile TSV for SQL loading")
args = parser.parse_args()

in_tsv = args.in_tsv
out_sql = args.out_sql

# outputs
def run_patmatmotifs(in_fasta):
    """
    Function to run patmatmotifs from stdin and write output to stdout
    :param in_fasta: stdin in FASTA format
    :type in_fasta: str
    :return: report text
    :rtype: str
    """
    command = ["patmatmotifs", "-auto", "-filter", "-stdout", "-rformat2", "excel"]
    # Accepting stdin, capturing output
    process = subprocess.run(command, input=in_fasta, text=True, capture_output=True)
    # Checking rcode
    if process.returncode != 0:
        print("patmatmotifs failed\n")
        print(process.stderr)
        sys.exit(process.returncode)

    return process.stdout

# Creating FASTA stdin and generating hits list
hits = []
with open(in_tsv, 'r') as fcon:
    for rec in fcon:
        row = rec.split("\t")
        # verifying input
        if len(row) < 3:
            continue
        acc = row[0].strip()
        seq = row[2].strip()
        if not acc or not seq:
            continue

        # FASTA stdin
        fasta_text = f">{acc}\n{seq}\n"

        # Tab-delimited report for SQL
        excel_out = run_patmatmotifs(fasta_text)
        
        # Parsing for SQL
        # Splitting and parsing lines
        lines = excel_out.splitlines()
        for line in lines:
            # Checking for header
            if line.startswith("SeqName\t"):
                continue
            hit = line.split("\t")
            # Verifying length
            if len(hit) != 6:
                continue
            # Fields are: SeqName Start End Score Strand Motif
            seqname, start, end, score, strand, motif = hit
            # Verifying types
            try:
                start = int(start)
                end = int(end)
            except:
                continue
            # Getting the motif sequence
            if start < 1 or end > len(seq):
                mot_seq = "" # TODO: perhaps should be Null
            else:
                mot_seq = seq[start-1:end]
            
            hits.append((acc, motif, start, end, score, mot_seq))

# Writing hits to out_sql file
with open(out_sql, "w") as outcon:
    for (seqname, motif, start, end, score, mot_seq) in hits:
        outcon.write(f"{seqname}\t{motif}\t{start}\t{end}\t{score}\t{mot_seq}\n")
