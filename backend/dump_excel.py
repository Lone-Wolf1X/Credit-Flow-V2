import pandas as pd
import os

file_path = r'd:\Credit Flow\Appraisal\Scoring Model MPOD and MTL (1).xlsx'

def dump_excel(file_path):
    if not os.path.exists(file_path):
        print(f"File not found: {file_path}")
        return

    try:
        xls = pd.ExcelFile(file_path)
        print(f"Sheets: {xls.sheet_names}")
        
        for sheet_name in xls.sheet_names:
            print(f"\n--- Sheet: {sheet_name} ---")
            df = pd.read_excel(xls, sheet_name=sheet_name)
            print(df.to_markdown(index=False))
            
    except Exception as e:
        print(f"Error reading file {file_path}: {e}")

if __name__ == "__main__":
    dump_excel(file_path)
