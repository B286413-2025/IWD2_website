#!/usr/bin/python3

## Downloading example set glucose-6-phosphatase proteins from Aves (birds)

# Modules
import os, subprocess, sys

# Perhaps activating the correct environment?
#subprocess.run("conda activate pd", shell=True)

from Bio import Entrez, SeqIO


# Entrez parameters
Entrez.email = "dandush1001@gmail.com"
Entrez.api_key = subprocess.check_output("echo ${NCBI_API_KEY}", shell=True).rstrip().decode('utf-8')

# Data to download
prot_fam = "glucose-6-phosphatase".lower()
tax_group = "Aves".lower()

# Out files to MySQL and ClustalO, respectively
sql_file = "/home/s2883992/public_html/website/downloaded_sequences/example_record.csv"
fa_file = "/home/s2883992/public_html/website/downloaded_sequences/example_record.fasta"

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
with open(sql_file, 'w') as filecon:
    with open(fa_file, 'w') as facon:
        # Getting the relevant information
        handle = Entrez.efetch(db='protein', id=result['IdList'], rettype='gb', retmode='text')
        rec_iter = SeqIO.parse(handle, 'gb')
        for record in rec_iter:
            # Checking for under 5% of ambiguous bases
            if (record.seq.lower().count('x') / len(record.seq)) < 0.05:
                # csv file for MySQL
                filecon.write(f'{record.name},{record.annotations["organism"]},{record.seq}\n')
                # FASTA format for ClustO
                # Adapted from: https://warwick.ac.uk/fac/sci/moac/people/students/peter_cock/python/genbank2fasta/
                facon.write(f'>{record.id} {record.description}\n{record.seq}\n')

print("Done downloading sequences...")
