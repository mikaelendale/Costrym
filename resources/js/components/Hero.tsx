import landingImage from '@/assets/landingImage.jpg';
import { Button } from './ui/button';

const Hero = () => {
    return (
        // make the full component bg an image from landingImage.jpg
        <div
            className="flex h-screen flex-col items-center justify-center gap-4 bg-cover bg-center bg-no-repeat"
            style={{ backgroundImage: `url(${landingImage})` }}
        >
            <div className="flex flex-col items-center align-middle">
                <h1 className="w-full text-5xl leading-tight font-bold text-balance sm:text-6xl">Cut 15%–40% of Your Business</h1>
                <h1 className="flex w-full justify-center text-5xl leading-tight font-bold text-balance sm:text-6xl">Costs starting today</h1>
            </div>
            <p className="mb-8 text-lg leading-relaxed text-muted-foreground">
                If you are overspending <b>Costrym</b> Will Find It, Cut it and Increase your profitability
            </p>
            <div className="flex justify-center gap-4">
                <Button asChild>
                    <a href="https://app.costrym.com/register">Get Started</a>
                </Button>
                <Button variant="outline" onClick={() => document.getElementById('requirements')?.scrollIntoView({ behavior: 'smooth' })}>
                    Check if you qualify
                </Button>
            </div>
        </div>
    );
};

export default Hero;
