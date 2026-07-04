import { type Key, type ReactNode } from 'react';

function renderInlineMarkdown(text: string) {
    const nodes: ReactNode[] = [];
    const pattern = /(\*\*[^*]+\*\*|\*[^*]+\*|`[^`]+`)/g;
    let lastIndex = 0;

    text.replace(pattern, (match, _token, offset) => {
        if (offset > lastIndex) {
            nodes.push(text.slice(lastIndex, offset));
        }

        if (match.startsWith('**')) {
            nodes.push(
                <strong key={`${offset}-${match}`} className="text-foreground font-semibold">
                    {match.slice(2, -2)}
                </strong>,
            );
        } else if (match.startsWith('*')) {
            nodes.push(
                <em key={`${offset}-${match}`} className="italic">
                    {match.slice(1, -1)}
                </em>,
            );
        } else {
            nodes.push(
                <code key={`${offset}-${match}`} className="bg-muted text-foreground rounded px-1 py-0.5 text-xs">
                    {match.slice(1, -1)}
                </code>,
            );
        }

        lastIndex = offset + match.length;
        return match;
    });

    if (lastIndex < text.length) {
        nodes.push(text.slice(lastIndex));
    }

    return nodes.length > 0 ? nodes : text;
}

function renderMarkdownLine(line: string, key: Key) {
    const trimmedLine = line.trim();
    const heading = /^(#{1,4})\s+(.+)$/.exec(trimmedLine);

    if (heading) {
        const level = heading[1].length;
        const content = renderInlineMarkdown(heading[2]);

        if (level === 1) {
            return (
                <h2 key={key} className="text-foreground pt-1 text-xl font-semibold tracking-tight">
                    {content}
                </h2>
            );
        }

        if (level === 2) {
            return (
                <h3 key={key} className="text-foreground pt-3 text-base font-semibold">
                    {content}
                </h3>
            );
        }

        return (
            <h4 key={key} className="text-foreground pt-2 text-sm font-semibold">
                {content}
            </h4>
        );
    }

    if (/^[-*_]{3,}$/.test(trimmedLine)) {
        return <hr key={key} className="border-sidebar-border/70 dark:border-sidebar-border" />;
    }

    return (
        <p key={key} className="text-muted-foreground whitespace-pre-wrap">
            {renderInlineMarkdown(trimmedLine)}
        </p>
    );
}

export default function SummaryMarkdown({ text }: { text: string }) {
    const lines = text.replace(/\r\n/g, '\n').split('\n');
    const elements: ReactNode[] = [];
    let listItems: string[] = [];

    const flushList = () => {
        if (listItems.length === 0) {
            return;
        }

        elements.push(
            <ul key={`list-${elements.length}`} className="text-muted-foreground list-disc space-y-1 pl-5">
                {listItems.map((item, index) => (
                    <li key={index}>{renderInlineMarkdown(item)}</li>
                ))}
            </ul>,
        );
        listItems = [];
    };

    lines.forEach((line, index) => {
        const trimmedLine = line.trim();

        if (!trimmedLine) {
            flushList();
            return;
        }

        const bullet = /^[-*]\s+(.+)$/.exec(trimmedLine);

        if (bullet) {
            listItems.push(bullet[1]);
            return;
        }

        flushList();
        elements.push(renderMarkdownLine(trimmedLine, `line-${index}`));
    });

    flushList();

    return <div className="space-y-3 text-sm leading-7">{elements}</div>;
}
