import { SimpleHeader } from '@/components/simple-header';
import React from 'react';

const MainLandingLayout = ({ children }: { children: React.ReactNode }) => {
    return (
        <div className="mx-auto h-full w-full max-w-5xl bg-[var(--background)]">
            <SimpleHeader />
            <div>{children}</div>
        </div>
    );
};

export default MainLandingLayout;
