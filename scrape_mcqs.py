import re
import sys
import json
import html
import time
import requests
from bs4 import BeautifulSoup

def clean_html(text):
    # Remove HTML tags
    text = re.sub(r'<[^>]+>', '', text)
    return text.strip()

def scrape_category(category_slug, max_pages=1):
    base_url = f"https://pakmcqs.com/category/{category_slug}"
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    }
    
    # Map slug to database category name
    category_map = {
        'computer-mcqs': 'Computer Science',
        'physics-mcqs': 'Physics',
        'biology-mcqs': 'Biology',
        'chemistry-mcqs': 'Chemistry',
        'mathematics-mcqs': 'Mathematics',
        'general-knowledge-mcqs': 'General Knowledge',
        'pak-study-mcqs': 'Pak Study',
        'islamic-studies-mcqs': 'Islamic Studies',
        'english-mcqs': 'English'
    }
    
    db_category = category_map.get(category_slug, category_slug.replace('-', ' ').title())
    
    all_mcqs = []
    
    for page in range(1, max_pages + 1):
        url = base_url if page == 1 else f"{base_url}/page/{page}"
        print(f"Scraping page {page}: {url}")
        
        try:
            response = requests.get(url, headers=headers, timeout=15)
            if response.status_code == 404:
                print(f"Page {page} not found (404). Ending scraping.")
                break
            if response.status_code != 200:
                print(f"Failed to fetch page {page}: Status code {response.status_code}")
                continue
                
            soup = BeautifulSoup(response.content, 'html.parser')
            articles = soup.find_all('article', class_='l-post')
            
            if not articles:
                print(f"No articles found on page {page}.")
                break
                
            page_mcqs_count = 0
            for art in articles:
                # Question text
                title_h2 = art.find('h2', class_='post-title')
                if not title_h2:
                    continue
                title_a = title_h2.find('a')
                if not title_a:
                    continue
                question_text = title_a.get_text().strip()
                # Clean up any trailing question mark issues
                question_text = html.unescape(question_text)
                
                # Excerpt containing options
                excerpt_div = art.find('div', class_='excerpt')
                if not excerpt_div:
                    continue
                    
                # Find correct option by looking for bolded text
                correct_letter = None
                strong_tags = excerpt_div.find_all(['strong', 'b'])
                for tag in strong_tags:
                    tag_text = tag.get_text().strip()
                    match = re.match(r'^([A-D])\s*[\.\):]', tag_text, re.IGNORECASE)
                    if match:
                        correct_letter = match.group(1).upper()
                        break
                
                # Split options
                html_str = excerpt_div.decode_contents()
                lines = [line.strip() for line in re.split(r'<br\s*/?>|\n', html_str) if line.strip()]
                
                options = {'A': '', 'B': '', 'C': '', 'D': ''}
                for line in lines:
                    clean_line = clean_html(line)
                    # Remove "Submitted by..." if it's on this line
                    clean_line = re.sub(r'Submitted by.*', '', clean_line, flags=re.IGNORECASE).strip()
                    
                    match = re.match(r'^([A-D])\s*[\.\):]\s*(.*)', clean_line, re.IGNORECASE)
                    if match:
                        opt_letter = match.group(1).upper()
                        opt_text = match.group(2).strip()
                        options[opt_letter] = html.unescape(opt_text)
                        
                        # In case correct letter wasn't found yet, check if this option line is bolded
                        if not correct_letter:
                            if '<strong>' + line in html_str or '<b>' + line in html_str or '<strong>' in line or '<b>' in line:
                                correct_letter = opt_letter

                # Check if we got options
                if not any(options.values()):
                    continue
                    
                # Default correct answer to A if not found (fallback)
                if not correct_letter:
                    correct_letter = 'A'
                    
                all_mcqs.append({
                    'question': question_text,
                    'option_a': options['A'],
                    'option_b': options['B'],
                    'option_c': options['C'],
                    'option_d': options['D'],
                    'correct_answer': correct_letter,
                    'category': db_category
                })
                page_mcqs_count += 1
                
            print(f"Successfully parsed {page_mcqs_count} MCQs from page {page}.")
            time.sleep(1) # Polite delay
            
        except Exception as e:
            print(f"Error scraping page {page}: {e}")
            
    return all_mcqs

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("Usage: python scrape_mcqs.py <category_slug> [max_pages]")
        print("Example: python scrape_mcqs.py computer-mcqs 2")
        sys.exit(1)
        
    category_slug = sys.argv[1]
    max_pages = int(sys.argv[2]) if len(sys.argv) > 2 else 1
    
    print(f"Starting scraping for: {category_slug} (Max pages: {max_pages})")
    mcqs = scrape_category(category_slug, max_pages)
    
    if mcqs:
        with open('scraped_mcqs.json', 'w', encoding='utf-8') as f:
            json.dump(mcqs, f, indent=4, ensure_ascii=False)
        print(f"Scraping completed. Saved {len(mcqs)} MCQs to scraped_mcqs.json")
    else:
        print("No MCQs scraped.")
