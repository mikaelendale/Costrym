import { SimpleHeader } from '@/components/simple-header';
import React from 'react';

const MainLandingLayout = ({ children }: { children: React.ReactNode }) => {
    return (
        <div className="">
            <SimpleHeader />
            <div className="flex flex-col gap-1">{children}</div>
        </div>
    );
};

export default MainLandingLayout;
