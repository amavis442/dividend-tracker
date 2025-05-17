# pip3 install cloudscraper beautifulsoup4
from bs4 import BeautifulSoup
import cloudscraper
import pathlib

# create a cloudscraper instance
scraper = cloudscraper.create_scraper(
    browser={
        "browser": "chrome",
        "platform": "windows",
    },
)

# specify the target URL
url = "https://globalxetfs.eu/funds/xylu"

# request the target website
response = scraper.get(url)

# get the response status code
#print(f"The status code is {response.status_code}")

# parse the returned HTML
#soup = BeautifulSoup(response.text, "html.parser")

# get the description element
#page_description = soup.select_one(".dcal")

# print the description text
#print(response.text)
#print(page_description.text)

pathlib.Path("~/globalx/sp500.html").unlink(missing_ok=True)
file = open("~/globalx/sp500.html", "w")
file.write(response.text)
file.close()
