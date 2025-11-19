import CostrymLogo from '@/assets/CostrymLogo.png';
import { FaInstagram, FaLinkedin } from 'react-icons/fa';
import { FaXTwitter } from 'react-icons/fa6';

const socialLinks = [
    {
        name: 'X (Twitter)',
        href: 'https://x.com/costrym',
        icon: <FaXTwitter className="h-5 w-5" aria-label="X (Twitter)" />,
    },
    {
        name: 'LinkedIn',
        href: 'https://www.linkedin.com/company/costrym',
        icon: <FaLinkedin className="h-5 w-5" aria-label="LinkedIn" />,
    },
    {
        name: 'Instagram',
        href: 'https://www.instagram.com/costrym.ai/',
        icon: <FaInstagram className="h-5 w-5" aria-label="Instagram" />,
    },
];

export function Footer() {
    return (
        <footer className="border-t border-border px-4 py-12 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-6xl">
                <div className="mb-12 grid grid-cols-1 gap-8 md:grid-cols-4">
                    <div>
                        <div className="mb-4 flex items-center gap-2 font-bold">
                            <img src={CostrymLogo} alt="Costrym Logo" className="h-8 w-8" />
                            <span>Costrym</span>
                        </div>
                        <p className="text-sm text-muted-foreground">Cost optimization, simplified.</p>
                        <div className="mt-4 flex gap-4">
                            {socialLinks.map((link) => (
                                <a
                                    key={link.name}
                                    href={link.href}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-muted-foreground transition hover:text-foreground"
                                    aria-label={link.name}
                                >
                                    {link.icon}
                                </a>
                            ))}
                        </div>
                    </div>
                </div>
                <div className="border-t border-border pt-8 text-center text-sm text-muted-foreground">
                    <p>&copy; 2025 Costrym. All rights reserved.</p>
                </div>
            </div>
        </footer>
    );
}
