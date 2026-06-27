import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" fill="none">
            <path d="M11 4.5h12.5L31 12v23.5H11z" stroke="currentColor" strokeWidth="3" strokeLinejoin="round" />
            <path d="M23.5 4.5V12H31" stroke="currentColor" strokeWidth="3" strokeLinejoin="round" />
            <path d="M16 19h11" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" />
            <path d="M16 25h8" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" />
            <path d="M16 31h5" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" />
            <path d="M28.5 27.5l1.1 2.2 2.4 0.4-1.7 1.7 0.4 2.4-2.2-1.1-2.2 1.1 0.4-2.4-1.7-1.7 2.4-0.4z" fill="currentColor" />
        </svg>
    );
}
