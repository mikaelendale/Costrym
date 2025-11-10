import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <img src='/logo.svg' className='h-8 w-8 bg-whitey' {...props} />
    );
}
