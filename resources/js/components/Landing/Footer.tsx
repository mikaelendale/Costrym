export function Footer() {
    const columns = [
        { title: 'Product', links: ['Features', 'Pricing', 'Security'] },
        { title: 'Company', links: ['About', 'Blog', 'Careers'] },
        { title: 'Legal', links: ['Privacy', 'Terms', 'Contact'] },
    ];

    return (
        <footer className="border-t border-border px-4 py-12 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-6xl">
                <div className="mb-12 grid grid-cols-1 gap-8 md:grid-cols-4">
                    <div>
                        <div className="mb-4 flex items-center gap-2 font-bold">
                            <div className="flex h-6 w-6 items-center justify-center rounded-md bg-primary text-xs text-primary-foreground">⊞</div>
                            <span>Costrym</span>
                        </div>
                        <p className="text-sm text-muted-foreground">Cost optimization, simplified.</p>
                    </div>
                    {columns.map((col, i) => (
                        <div key={i}>
                            <h3 className="mb-4 font-bold">{col.title}</h3>
                            <ul className="space-y-2">
                                {col.links.map((link, j) => (
                                    <li key={j}>
                                        <a href="#" className="text-sm text-muted-foreground transition hover:text-foreground">
                                            {link}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>
                <div className="border-t border-border pt-8 text-center text-sm text-muted-foreground">
                    <p>&copy; 2025 Costrym. All rights reserved.</p>
                </div>
            </div>
        </footer>
    );
}
