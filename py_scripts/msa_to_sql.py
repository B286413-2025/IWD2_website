#!/usr/bin/python3

## Performing ClustalO on fasta sequences and generating a tsvfile for SQL loading

# Modules
import os, subprocess, sys, argparse

from Bio import SeqIO

# Command line arguments
parser = argparse.ArgumentParser(
        prog='RunClustalO',
        description='MSA with ClustalO and tsv file for SQL')
parser.add_argument('infile', action='store', help='Input FASTA file')
parser.add_argument('outfmt', action='store', help="Outfile format")
parser.add_argument('outfile', action='store', help="Outfile path")
parser.add_argument('tsvpath', action='store', help="TSV file path for SQL loadinng")
args = parser.parse_args()

# ClustalO parameters
infile = args.infile
outfmt = args.outfmt.lower()
outfile = args.outfile
# Updating outfile extension
if not(outfile.endswith(outfmt)):
    outfile = f"{outfile}.{outfmt}"
tsv_path = args.tsvpath

# ClustalO
command = f"clustalo -i {infile} --outfmt {outfmt} -o {outfile} --threads 12 --force"
process = subprocess.run(command, shell = True)
if process.returncode != 0:
    print("ClustalO failed")
    print(process.stderr)
    sys.exit(process.returncode)

# Out file to enter into MySQL
# Downloading sequences while assessing quality:
if outfmt == "fasta":
    with open(tsv_path, 'w') as filecon:
    # Getting the relevant information
        for record in SeqIO.parse(outfile, 'fasta'):
            filecon.write(f'{record.id}\t{str(record.seq)}\n')
print("Done aligning sequences...")
