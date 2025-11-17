import { Button } from './ui/button';

const Hero = () => {
    return (
        <div className="flex h-screen flex-col items-center justify-center gap-4 bg-cover bg-center bg-no-repeat">
            <div className="flex flex-col items-center">
                <h1 className="text-5xl leading-tight font-bold text-balance sm:text-6xl">If You're Overspending</h1>
                <h1 className="mb-3 text-5xl leading-tight font-bold text-balance sm:text-6xl">Costrym Will Find It</h1>
            </div>
            <p className="mb-8 text-xl leading-relaxed text-muted-foreground">Cut it and Increase your profitability</p>
            <div className="flex justify-center gap-4">
                <Button>Get Started</Button>
                <Button variant="outline">Check if you qualify</Button>
            </div>
        </div>
    );
};

export default Hero;
