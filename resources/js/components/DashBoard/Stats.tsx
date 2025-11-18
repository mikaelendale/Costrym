import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../ui/card';

const Stats = () => {
    const saved = '$342,567';
    const savedYear = '$4,110,804';
    const optimizations = '18';

    return (
        <div className="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
            {/* Saved Per Month */}
            <Card className="w-full">
                <CardHeader className="px-6">
                    <CardDescription>Saved Per Month</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex items-center gap-2">
                        <CardTitle className="text-3xl tracking-tight sm:text-4xl">{saved}</CardTitle>
                        <span className="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                className="h-3.5 w-3.5"
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
                <CardHeader className="px-6">
                    <CardDescription>Saved Per Year</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex items-center gap-2">
                        <CardTitle className="text-3xl tracking-tight sm:text-4xl">{savedYear}</CardTitle>
                        <span className="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                className="h-3.5 w-3.5"
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
                <CardHeader className="px-6">
                    <CardDescription>Optimizations</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex items-center gap-2">
                        <CardTitle className="text-3xl tracking-tight sm:text-4xl">{optimizations}</CardTitle>
                        <span className="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                className="h-3.5 w-3.5"
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
