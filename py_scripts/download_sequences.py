#!/usr/bin/python3

## Downloading sequences for protein family and a taxonomic group
## defaults: glucose-6-phosphatase proteins from Aves (birds)

# Modules
import os, sys, argparse
from Bio import Entrez, SeqIO

# Command line arguments, 
# Debugged using ELM (GPT 5.2), https://elm.edina.ac.uk/elm/elm
parser = argparse.ArgumentParser()
parser.add_argument("--protein", default="glucose-6-phosphatase")
parser.add_argument("--taxon", default="Aves")
parser.add_argument("--outdir", required=True)

# Filtering thresholds
parser.add_argument("--minlen", type=int, default=50,
                    help="Minimum allowed protein length (default: 50)")
parser.add_argument("--maxlen", type=int, default=2000,
                    help="Maximum allowed protein length (default: 2000)")
parser.add_argument("--max_x_frac", type=float, default=0.05,
                    help="Maximum allowed fraction of X residues (default: 0.05)")

# Retrieval limit
parser.add_argument("--retmax", type=int, default=1000,
                    help="Maximum number of records to retrieve from NCBI (default: 1000)")

# Total size limits for the kept dataset (amino acids and number of sequences)
parser.add_argument("--max_total_aa", type=int, default=200000)
parser.add_argument("--max_kept", type=int, default=500)

args = parser.parse_args()

# Out files to MySQL loading and ClustalO
outdir = args.outdir
os.makedirs(outdir, exist_ok=True)
tsv_file = os.path.join(outdir, "example_record.tsv")
fa_file  = os.path.join(outdir, "example_record.fasta")

# Entrez parameters
## TODO: probably shouldn't hard-code
Entrez.email = "dandush1001@gmail.com"
Entrez.api_key = "237a0a96e905e613335cbcffdd23be96e209"

# NCBI query
prot_fam = args.protein.lower()
tax_group = args.taxon.lower()
query = f"{prot_fam}[Prot] AND {tax_group}[Organism] NOT partial"

# searching, limiting results
search = Entrez.esearch(db='protein', term=query, retmax=args.retmax)

# Processing results, checking for number of matches
result = Entrez.read(search)
raw_match_num = int(result['Count'])

# Downloading sequences while filtering
# Initializing a counter for kept sequences and sum of amino acids
kept_num = 0
total_aa = 0

with open(tsv_file, 'w') as tsv, open(fa_file, 'w') as fa:
    # If no records were found
    if raw_match_num == 0:
        print(0)
        print("NCBI matches: 0")
        print("Total amino acids kept: 0")
        sys.exit(0)

    # Getting the relevant information
    handle = Entrez.efetch(db='protein', id=result['IdList'], rettype='gb', retmode='text')
    for record in  SeqIO.parse(handle, 'gb'):
        seq = str(record.seq)
        # Filtering checks
        # Not empty
        if len(seq) == 0:
            continue

        # Appropriate length
        if len(seq) < args.minlen:
            continue
        if len(seq) > args.maxlen:
            continue

        # Ambiguous residues (X)
        # TODO: possibly add the rest
        if (seq.lower().count('x') / len(seq)) > args.max_x_frac:
            continue

        # Total dataset size limit, stopping if passed
        if args.max_kept > 0 and kept_num >= args.max_kept:
            break
        if args.max_total_aa > 0 and (total_aa + len(seq)) > args.max_total_aa:
            break

        # Otherwise, writing to files
        # TSV file for MySQL
        acc = record.name
        org = record.annotations.get("organism", "unknown")
        tsv.write(f'{acc}\t{org}\t{seq}\n')
        # FASTA format for ClustO
        # Adapted from: https://warwick.ac.uk/fac/sci/moac/people/students/peter_cock/python/genbank2fasta/
        fa.write(f'>{acc} {record.description}\n{seq}\n')
            
        # Updating counters
        kept_num += 1
        total_aa += len(seq)

handle.close()

# Print number of retained sequences
print(kept_num)

# Extra non-numeric diagnostics
print(f"NCBI matches: {raw_match_num}")
print(f"Total amino acids kept: {total_aa}")
print(f"Filters used: minlen={args.minlen}, maxlen={args.maxlen}, max_x_frac={args.max_x_frac}, max_total_aa={args.max_total_aa}, max_kept={args.max_kept}")
