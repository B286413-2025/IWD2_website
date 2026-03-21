#!/usr/bin/python3

## Downloading example set glucose-6-phosphatase proteins from Aves (birds)

# Modules
import os, subprocess, sys, argparse

# Perhaps activating the correct environment?
#subprocess.run("conda activate pd", shell=True)

from Bio import Entrez, SeqIO

# Command line arguments
parser = argparse.ArgumentParser(
        prog='DownloadSeqs',
        description='Downloading protein family sequences from NCBI')
parser.add_argument('protein',action='store',help='Protein group')
parser.add_argument('taxon',action='store',help="Taxon group")
args = parser.parse_args()

# Entrez parameters
Entrez.email = "dandush1001@gmail.com"
Entrez.api_key = subprocess.check_output("echo ${NCBI_API_KEY}", shell=True).rstrip().decode('utf-8')

# Data to download
prot_fam = args.protein.lower()
tax_group = args.taxon.lower()

# Out file to enter into MySQL
##TODO: perhaps creating an interim unique filename and deleting in the end
sql_file = "/home/s2883992/public_html/website/downloaded_sequences/record.csv"

# NCBI query
query = f"{prot_fam}[Prot] AND {tax_group}[Organism] NOT partial"

# searching, limiting to 1000 results
search = Entrez.esearch(db='protein', term=query, retmax=1000)

# Processing results, checking for number of matches
result = Entrez.read(search)
match_num = int(result['Count'])
# Exiting for zero matches
if match_num == 0:
    print("No matches were found, exiting...")
    sys.exit()
# Deciding if to continue for over 1000 matches
elif match_num > 1000:
    print(f"{match_num} matches were found, can only continue with the first 1000 sequences.")
    cont = input("Would you like to continue? y/[n]\n").lower()
    if not (cont in ['y', 'yes']):
        print("Exiting...")
        sys.exit()
# Printing result number for under a 1000 matches
else:
    print(f"{match_num} matches were found, downloading sequences...")

# Downloading sequences while assessing quality:
with open(sql_file, 'a') as filecon:
    # Getting the relevant information
    handle = Entrez.efetch(db='protein', id=result['IdList'], rettype='gb', retmode='text')
    rec_iter = SeqIO.parse(handle, 'gb')
    for record in rec_iter:
        # Checking for under 5% of ambiguous bases
        if (record.seq.lower().count('x') / len(record.seq)) < 0.05:
            filecon.write(f'{record.name},{record.annotations["organism"]},{record.seq}\n')
print("Done downloading sequences...")
