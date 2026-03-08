import fs from "fs/promises";
import path from "path";
import * as cheerio from "cheerio";
import TurndownService from "turndown";
import pLimit from "p-limit";

const BASE_URL = "https://developer.wordpress.org/plugins/";
const OUTPUT_DIR = "./docs/wp";

const turndown = new TurndownService();
const limit = pLimit(5); // paralelismo

async function fetchHtml(url: string): Promise<string> {
    const res = await fetch(url);
    if (!res.ok) throw new Error(`Failed ${url}`);
    return res.text();
}

function extractMenuLinks(html: string): string[] {
    const $ = cheerio.load(html);

    const links: string[] = [];

    $("aside nav a").each((_, el) => {
        const href = $(el).attr("href");
        if (!href) return;

        if (href.startsWith("https://developer.wordpress.org/plugins")) {
            links.push(
                new URL(href, "https://developer.wordpress.org").toString(),
            );
        }
    });

    return [...new Set(links)];
}

function htmlToMarkdown(html: string): string {
    return turndown.turndown(html);
}

function slugFromUrl(url: string) {
    return (
        url.replace(BASE_URL, "").replace(/\/$/, "").replace(/\//g, "_") ||
        "index"
    );
}

async function scrapePage(url: string) {
    console.log("Scraping:", url);

    const html = await fetchHtml(url);
    const $ = cheerio.load(html);

    const article = $("main article").html();

    if (!article) {
        console.warn("No article found:", url);
        return;
    }

    const markdown = htmlToMarkdown(article);

    const slug = slugFromUrl(url);
    const file = path.join(OUTPUT_DIR, `${slug}.md`);

    await fs.writeFile(file, markdown);

    console.log("Saved:", file);
}

export async function scrapeWordpressDocs() {
    await fs.mkdir(OUTPUT_DIR, { recursive: true });

    const rootHtml = await fetchHtml(BASE_URL);

    const links = extractMenuLinks(rootHtml);

    console.log({ links });

    console.log(`Found ${links.length} pages`);

    await Promise.all(links.map((link) => limit(() => scrapePage(link))));

    console.log("Done");
}

if (import.meta.main) {
    scrapeWordpressDocs();
}
