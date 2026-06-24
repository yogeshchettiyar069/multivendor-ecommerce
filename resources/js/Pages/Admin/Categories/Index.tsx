import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface Leaf {
    id: string;
    name: string;
    product_count: number;
}
interface Root extends Leaf {
    children: Leaf[];
}

interface Props {
    tree: Root[];
    parents: Array<{ id: string; name: string }>;
}

export default function AdminCategories({ tree, parents }: Props) {
    const addForm = useForm({ name: '', parent_id: 'root' });
    const [editing, setEditing] = useState<{ id: string; name: string } | null>(null);
    const [deleting, setDeleting] = useState<{ id: string; name: string } | null>(null);

    const submitAdd = (e: FormEvent) => {
        e.preventDefault();
        addForm.transform((d) => ({
            ...d,
            parent_id: d.parent_id === 'root' ? null : d.parent_id,
        }));
        addForm.post(route('admin.categories.store'), {
            preserveScroll: true,
            onSuccess: () => addForm.reset(),
        });
    };

    const saveRename = () => {
        if (!editing) return;
        router.patch(
            route('admin.categories.update', editing.id),
            { name: editing.name },
            { preserveScroll: true, onSuccess: () => setEditing(null) },
        );
    };

    const confirmDelete = () => {
        if (!deleting) return;
        router.delete(route('admin.categories.destroy', deleting.id), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    const row = (c: Leaf, child = false) => (
        <div
            key={c.id}
            className={
                'flex items-center justify-between py-2 ' +
                (child ? 'border-l border-border pl-4' : '')
            }
        >
            <span className="text-sm">
                {c.name}
                <span className="ml-2 text-xs text-muted-foreground">
                    {c.product_count} products
                </span>
            </span>
            <div className="flex gap-1">
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => setEditing({ id: c.id, name: c.name })}
                >
                    <Pencil className="h-4 w-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => setDeleting({ id: c.id, name: c.name })}
                >
                    <Trash2 className="h-4 w-4 text-destructive" />
                </Button>
            </div>
        </div>
    );

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-foreground">Categories</h2>}
        >
            <Head title="Categories" />

            <div className="mx-auto grid max-w-5xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_320px] lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Category tree</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {tree.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No categories yet.</p>
                        ) : (
                            <div className="divide-y divide-border">
                                {tree.map((root) => (
                                    <div key={root.id} className="py-2">
                                        <div className="font-medium">{row(root)}</div>
                                        {root.children.map((child) => row(child, true))}
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="h-fit">
                    <CardHeader>
                        <CardTitle>Add category</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitAdd} className="space-y-4">
                            <div>
                                <Label htmlFor="cat-name">Name</Label>
                                <Input
                                    id="cat-name"
                                    value={addForm.data.name}
                                    onChange={(e) => addForm.setData('name', e.target.value)}
                                    className="mt-1"
                                />
                                {addForm.errors.name && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {addForm.errors.name}
                                    </p>
                                )}
                            </div>
                            <div>
                                <Label>Parent</Label>
                                <Select
                                    value={addForm.data.parent_id}
                                    onValueChange={(v) => addForm.setData('parent_id', v)}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="root">— Top level —</SelectItem>
                                        {parents.map((p) => (
                                            <SelectItem key={p.id} value={p.id}>
                                                {p.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button type="submit" className="w-full" disabled={addForm.processing}>
                                <Plus className="h-4 w-4" /> Add category
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <Modal show={editing !== null} onClose={() => setEditing(null)} maxWidth="sm">
                <div className="p-6">
                    <h3 className="mb-3 text-lg font-semibold">Rename category</h3>
                    <Input
                        value={editing?.name ?? ''}
                        onChange={(e) =>
                            setEditing((s) => (s ? { ...s, name: e.target.value } : s))
                        }
                    />
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="outline" onClick={() => setEditing(null)}>
                            Cancel
                        </Button>
                        <Button onClick={saveRename}>Save</Button>
                    </div>
                </div>
            </Modal>

            <Modal show={deleting !== null} onClose={() => setDeleting(null)} maxWidth="sm">
                <div className="p-6">
                    <h3 className="text-lg font-semibold">Delete "{deleting?.name}"?</h3>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Categories with subcategories or products can't be deleted.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="outline" onClick={() => setDeleting(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete}>
                            Delete
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
