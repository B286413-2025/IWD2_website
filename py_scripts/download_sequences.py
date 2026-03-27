#!/usr/bin/python3

## Downloading sequences for protein family and a taxonomic group
## defaults: glucose-6-phosphatase proteins from Aves (birds)

# Modules
import os, sys, argparse
from Bio import Entrez, SeqIO

# Command line arguments, debugged using ELM (GPT 5.2) 
# https://elm.edina.ac.uk/elm/elm
parser = argparse.ArgumentParser()
parser.add_argument("--protein", default="glucose-6-phosphatase")
parser.add_argument("--taxon", default="Aves")
parser.add_argument("--outdir", required=True)
args = parser.parse_args()

# Out files to MySQL and ClustalO, respectively
outdir = args.outdir
os.makedirs(outdir, exist_ok=True)
tsv_file = os.path.join(outdir, "example_record.tsv")
fa_file  = os.path.join(outdir, "example_record.fasta")

# Entrez parameters
Entrez.email = "dandush1001@gmail.com"
Entrez.api_key = "237a0a96e905e613335cbcffdd23be96e209"

# NCBI query
prot_fam = args.protein.lower()
tax_group = args.taxon.lower()
query = f"{prot_fam}[Prot] AND {tax_group}[Organism] NOT partial"

# searching, limiting to 1000 results
search = Entrez.esearch(db='protein', term=query, retmax=1000)

# Processing results, checking for number of matches
result = Entrez.read(search)
match_num = int(result['Count'])

# Printing number of results
print(match_num)

# Exiting for zero matches
if match_num == 0:
    sys.exit(0)

# Downloading sequences while assessing quality:
with open(tsv_file, 'w') as tsv, open(fa_file, 'w') as fa:
        # Getting the relevant information
        handle = Entrez.efetch(db='protein', id=result['IdList'], rettype='gb', retmode='text')
        for record in  SeqIO.parse(handle, 'gb'):
            seq = str(record.seq)
            # Checking it's not empty
            if len(seq) == 0:
                continue
            # Checking for under 5% of ambiguous bases
            if (seq.lower().count('x') / len(seq)) < 0.05:
                # tsv file for MySQL
                acc = record.name
                org = record.annotations.get("organism", "unknown")
                tsv.write(f'{acc}\t{org}\t{seq}\n')
                # FASTA format for ClustO
                # Adapted from: https://warwick.ac.uk/fac/sci/moac/people/students/peter_cock/python/genbank2fasta/
                fa.write(f'>{acc} {record.description}\n{seq}\n')

