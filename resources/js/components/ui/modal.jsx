import React, { useEffect } from "react";

// Generic Modal using Tailwind theme tokens (background, foreground, card, border, primary)
const Modal = ({ open, onClose, title, description, children, footer }) => {
    useEffect(() => {
        const onKey = (e) => {
            if (e.key === "Escape") onClose?.();
        };
        if (open) {
            document.addEventListener("keydown", onKey);
            document.body.style.overflow = "hidden";
        }
        return () => {
            document.removeEventListener("keydown", onKey);
            document.body.style.overflow = "";
        };
    }, [open, onClose]);

    if (!open) return null;

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-label={title || "Dialog"}
        >
            {/* Overlay */}
            <div
                className="absolute inset-0 bg-foreground/20 backdrop-blur-sm"
                onClick={onClose}
            />

            {/* Panel */}
            <div className="relative z-10 w-full max-w-lg overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-xl">
                <div className="flex items-start gap-3 border-b border-border px-5 py-4">
                    <h3 className="text-lg font-semibold leading-none tracking-tight">
                        {title}
                    </h3>
                </div>

                <div className="px-5 py-4">
                    {description ? (
                        <p className="mb-3 text-sm text-muted-foreground">
                            {description}
                        </p>
                    ) : null}
                    {children}
                </div>

                <div className="flex items-center justify-end gap-2 border-t border-border bg-background/40 px-5 py-3">
                    {footer}
                </div>
            </div>
        </div>
    );
};

export default Modal;
