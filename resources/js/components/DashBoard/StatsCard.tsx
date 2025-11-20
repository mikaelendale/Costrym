import { Card, CardContent, CardTitle } from '../ui/card';

const StatsCard = () => {
    const TotalSaved = 342_567;

    return (
        <div className="flex h-fit w-full gap-6">
            <Card className="w-full p-6">
                <CardTitle className="text-4xl">${TotalSaved.toLocaleString()}</CardTitle>
                <CardContent className="text-right">Total Saved Per Month</CardContent>
            </Card>
            <Card className="w-full p-6">
                <CardTitle className="text-4xl">${TotalSaved.toLocaleString()}</CardTitle>
                <CardContent className="text-right">Total Saved Per Month</CardContent>
            </Card>
            <Card className="w-full p-6">
                <CardTitle className="text-4xl">${TotalSaved.toLocaleString()}</CardTitle>
                <CardContent className="text-right">Total Saved Per Month</CardContent>
            </Card>
        </div>
    );
};

export default StatsCard;
