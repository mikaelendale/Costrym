import { Form, Head } from '@inertiajs/react';
import { Github, LoaderCircle, User } from 'lucide-react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { UserCheckIcon } from '@phosphor-icons/react';
import { Checkbox } from '@/components/ui/checkbox';

interface ReferralData {
    name: string;
    email: string;
    avatar?: string;
    referral_code: string;
}

interface RegisterPageProps {
    referralData?: ReferralData;
}

export default function Register({ referralData }: RegisterPageProps) {
    const handleGoogleLogin = () => {
        window.location.href = route('auth.redirect', 'google');
    };
    const handleGithubLogin = () => {
        window.location.href = route('auth.redirect', 'github');
    };

    return (
        <AuthLayout title="Create an account" description="Enter your details below to create your account">
            <Head title="Register" />

            {/* Referral Information */}
            {referralData && (
                <div className=" rounded-2xl border mt-2 bg-accent p-4">
                    <div className="flex items-center space-x-3">
                        <div className="flex-shrink-0">
                            {referralData.avatar ? (
                                <img
                                    src={referralData.avatar}
                                    alt={referralData.name}
                                    className="h-10 w-10 rounded-full object-cover"
                                />
                            ) : (
                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary-foreground">
                                    <UserCheckIcon className="h-5 w-5 text-primary" />
                                </div>
                            )}
                        </div>
                        <div className="flex-1">
                            <p className="text-sm font-normal text-primary">
                                You've been invited by {referralData.name}
                            </p>
                            <p className="text-xs font-normal">
                                Referral code: <span className="font-mono text-green-600 dark:text-green-400 font-normal">{referralData.referral_code}</span>
                            </p>
                        </div>
                    </div>
                </div>
            )}

            <Form
                method="post"
                action={route('register')}
                resetOnSuccess={['password']}
                disableWhileProcessing
                className="flex flex-col gap-6 pt-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Full name"
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    required
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <Button size={'lg'} type="submit" className="mt-2 w-full" tabIndex={5}>
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                                Create account
                            </Button>
                        </div>
                        {/* <div className="flex flex-col grid grid-cols-2 md:grid-cols-2 gap-4"> */}
                        <div className="flex gap-4">
                            <Button
                                onClick={handleGoogleLogin}
                                className="w-full cursor-pointer border-primary/20"
                                tabIndex={0}
                                size="lg"
                                variant="secondary"
                                role="button"
                                onKeyDown={(e) => e.key === 'Enter' && handleGoogleLogin()}
                            >
                                <div className="flex items-center justify-center mx-auto space-x-3">
                                    <div className="flex-shrink-0">
                                        <svg className="h-6 w-6" viewBox="0 0 24 24">
                                            <path
                                                fill="#4285F4"
                                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                            />
                                            <path
                                                fill="#34A853"
                                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                            />
                                            <path
                                                fill="#FBBC05"
                                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                            />
                                            <path
                                                fill="#EA4335"
                                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                            />
                                        </svg>
                                    </div>
                                    <div className="">
                                        <div className="text-sm font-medium text-primary">Google</div>
                                    </div>
                                </div>
                            </Button>

                            {/* GitHub Login Card */}
                            {/* <div
                                onClick={handleGithubLogin}
                                className="w-full cursor-pointer rounded-xl border border-dashed border-primary/20 bg-accent p-4"
                                tabIndex={0}
                                role="button"
                                onKeyDown={(e) => e.key === 'Enter' && handleGithubLogin()}
                            >
                                <div className="flex items-center space-x-3">
                                    <div className="flex-shrink-0">
                                        <div className="flex h-6 w-6 items-center justify-center rounded-full bg-gray-900 dark:bg-white">
                                            <Github className="h-4 w-4 text-white dark:text-gray-900" />
                                        </div>
                                    </div>
                                    <div className="flex-1">
                                        <div className="text-sm font-medium text-gray-900 dark:text-white">GitHub</div>
                                    </div>
                                </div>
                            </div> */}
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink href={route('login')} tabIndex={6}>
                                Log in
                            </TextLink>
                        </div>

                        <div className="text-center text-xs text-muted-foreground">
                           By continuing, you agree to our <TextLink href="/terms">Terms of Service</TextLink> and <TextLink href="/privacy">Privacy Policy</TextLink>.
                           <br />
                           You can check the pricing <TextLink href={route('pricing')}>here</TextLink>.
                        </div>



                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
