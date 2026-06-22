import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function VendorApply() {
    const { data, setData, post, processing, errors } = useForm({
        store_name: '',
        bio: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('vendor.apply.store'));
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-foreground">Become a Vendor</h2>}
        >
            <Head title="Become a Vendor" />

            <div className="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Open your store</CardTitle>
                        <CardDescription>
                            Tell us about your store. Once submitted, an admin will review your
                            application before you can publish products.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel htmlFor="store_name" value="Store name" />
                                <TextInput
                                    id="store_name"
                                    className="mt-1 block w-full"
                                    value={data.store_name}
                                    isFocused
                                    onChange={(e) => setData('store_name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.store_name} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="bio" value="About your store (optional)" />
                                <textarea
                                    id="bio"
                                    className="mt-1 block w-full rounded-md border-input bg-background text-foreground shadow-sm focus:border-primary focus:ring-primary"
                                    rows={4}
                                    value={data.bio}
                                    onChange={(e) => setData('bio', e.target.value)}
                                />
                                <InputError message={errors.bio} className="mt-2" />
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    Submit application
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
