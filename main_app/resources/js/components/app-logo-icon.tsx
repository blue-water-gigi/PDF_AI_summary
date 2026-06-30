import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" fill="none">
            <path
                d="M20 5.5c3.1 0 5.78 1.73 7.16 4.28 2.86.45 5.04 2.91 5.04 5.9 0 1.05-.27 2.04-.75 2.9 1.13 1.42 1.8 3.22 1.8 5.17 0 4.7-3.81 8.5-8.5 8.5-1.71 0-3.3-.5-4.63-1.37-1.37 2.19-3.8 3.62-6.62 3.62-4.23 0-7.75-3.37-7.75-7.63 0-1.68.55-3.24 1.48-4.5a8.46 8.46 0 0 1-1.48-4.79c0-4.7 3.81-8.5 8.5-8.5.53 0 1.06.05 1.56.15A8.1 8.1 0 0 1 20 5.5Z"
                stroke="currentColor"
                strokeWidth="2.8"
                strokeLinejoin="round"
            />
            <path
                d="M15.55 12.02 24.9 17.4v10.8M28.8 18.88l-9.35 5.4-9.35-5.4M11.2 23.12l9.35-5.4 9.35 5.4M24.45 27.98 15.1 22.6V11.8"
                stroke="currentColor"
                strokeWidth="2.35"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
