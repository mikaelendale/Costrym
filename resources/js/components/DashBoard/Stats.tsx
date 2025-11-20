import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../ui/card';

const Stats = () => {
    const saved = '$342,567';
    const savedYear = '$4,110,804';
    const optimizations = '18';

    return (
        <div className="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 md:grid-cols-3">
            {/* Saved Per Month */}
            <Card className="w-full">
                <CardHeader className="px-4 sm:px-6">
                    <CardDescription className="text-xs sm:text-sm">Saved Per Month</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap items-center gap-2">
                        <CardTitle className="min-w-0 text-[clamp(1.125rem,3.5vw,2.25rem)] leading-tight tracking-tight">{saved}</CardTitle>
                        <span className="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-[clamp(0.625rem,1.8vw,0.75rem)] text-muted-foreground">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                aria-hidden="true"
                                className="h-3 w-3 sm:h-3.5 sm:w-3.5"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M3 17l6-6 4 4 7-7" />
                            </svg>
                            30%
                        </span>
                    </div>
                    {/* <div className="mt-2 text-sm text-muted-foreground">
                        vs yesterday: <span className="font-semibold text-foreground">{savedYesterday}</span>
                    </div> */}
                </CardContent>
            </Card>

            {/* Saved Per Year */}
            <Card className="w-full">
                <CardHeader className="px-4 sm:px-6">
                    <CardDescription className="text-xs sm:text-sm">Saved Per Year</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap items-center gap-2">
                        <CardTitle className="min-w-0 text-[clamp(1.125rem,3.5vw,2.25rem)] leading-tight tracking-tight">{savedYear}</CardTitle>
                        <span className="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-[clamp(0.625rem,1.8vw,0.75rem)] text-muted-foreground">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                aria-hidden="true"
                                className="h-3 w-3 sm:h-3.5 sm:w-3.5"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M3 17l6-6 4 4 7-7" />
                            </svg>
                            22%
                        </span>
                    </div>
                    {/* <div className="mt-2 text-sm text-muted-foreground">
                        vs yesterday: <span className="font-semibold text-foreground">{savedYearYesterday}</span>
                    </div> */}
                </CardContent>
            </Card>

            {/* Optimizations */}
            <Card className="w-full">
                <CardHeader className="px-4 sm:px-6">
                    <CardDescription className="text-xs sm:text-sm">Optimizations</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap items-center gap-2">
                        <CardTitle className="min-w-0 text-[clamp(1.125rem,3.5vw,2.25rem)] leading-tight tracking-tight">{optimizations}</CardTitle>
                        <span className="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-[clamp(0.625rem,1.8vw,0.75rem)] text-muted-foreground">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                aria-hidden="true"
                                className="h-3 w-3 sm:h-3.5 sm:w-3.5"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M3 17l6-6 4 4 7-7" />
                            </svg>
                            12%
                        </span>
                    </div>
                    {/* <div className="mt-2 text-sm text-muted-foreground">
                        vs yesterday: <span className="font-semibold text-foreground">{optimizationsYesterday}</span>
                    </div> */}
                </CardContent>
            </Card>
        </div>
    );
};

export default Stats;
