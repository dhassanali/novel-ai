import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from '@/components/ui/dialog';
import { useState } from 'react';
import { store, show } from '@/actions/App/Http/Controllers/NovelController';

interface Novel {
    id: number;
    title: string;
    description: string;
    genre: string;
    created_at: string;
}

interface Props {
    novels: Novel[];
}

export default function Index({ novels }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        genre: '',
    });
    const [open, setOpen] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store.url(), {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Novels', href: '/novels' }]}>
            <Head title="My Novels" />
            <div className="p-6">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold">My Novels</h1>
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button>Create Novel</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Create New Novel</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={submit} className="space-y-4">
                                <div>
                                    <Label htmlFor="title">Title</Label>
                                    <Input id="title" value={data.title} onChange={e => setData('title', e.target.value)} />
                                    {errors.title && <div className="text-red-500 text-sm">{errors.title}</div>}
                                </div>
                                <div>
                                    <Label htmlFor="genre">Genre</Label>
                                    <Input id="genre" value={data.genre} onChange={e => setData('genre', e.target.value)} />
                                </div>
                                <div>
                                    <Label htmlFor="description">Description</Label>
                                    <Input id="description" value={data.description} onChange={e => setData('description', e.target.value)} />
                                </div>
                                <DialogFooter>
                                    <Button type="submit" disabled={processing}>Create</Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {novels.map(novel => (
                        <Link key={novel.id} href={show.url({ novel: novel.id })}>
                            <Card className="hover:bg-accent/50 transition-colors cursor-pointer h-full">
                                <CardHeader>
                                    <CardTitle>{novel.title}</CardTitle>
                                    <CardDescription>{novel.genre}</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-muted-foreground line-clamp-3">{novel.description}</p>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                    {novels.length === 0 && (
                        <div className="col-span-full text-center py-12 text-muted-foreground">
                            No novels found. Create one to get started!
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
