import { cn } from '@/lib/utils';
import { ImagePlus, X } from 'lucide-react';
import { useRef, useState } from 'react';

interface ImageDropzoneProps {
    value: File | null;
    existingUrl?: string | null;
    onChange: (file: File | null) => void;
    error?: string;
}

export default function ImageDropzone({ value, existingUrl, onChange, error }: ImageDropzoneProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [dragging, setDragging] = useState(false);

    const previewUrl = value ? URL.createObjectURL(value) : (existingUrl ?? null);

    const handleFiles = (files: FileList | null) => {
        const file = files?.[0];
        if (file && file.type.startsWith('image/')) {
            onChange(file);
        }
    };

    return (
        <div>
            <div
                role="button"
                tabIndex={0}
                onClick={() => inputRef.current?.click()}
                onKeyDown={(e) => (e.key === 'Enter' || e.key === ' ') && inputRef.current?.click()}
                onDragOver={(e) => {
                    e.preventDefault();
                    setDragging(true);
                }}
                onDragLeave={() => setDragging(false)}
                onDrop={(e) => {
                    e.preventDefault();
                    setDragging(false);
                    handleFiles(e.dataTransfer.files);
                }}
                className={cn(
                    'relative flex min-h-44 cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-4 text-center transition-colors focus:outline-none focus:ring-2 focus:ring-ring',
                    dragging ? 'border-primary bg-primary/5' : 'border-input hover:border-primary/50',
                )}
            >
                {previewUrl ? (
                    <>
                        <img
                            src={previewUrl}
                            alt="Product preview"
                            className="max-h-40 rounded-md object-contain"
                        />
                        <button
                            type="button"
                            onClick={(e) => {
                                e.stopPropagation();
                                onChange(null);
                                if (inputRef.current) inputRef.current.value = '';
                            }}
                            className="absolute right-2 top-2 rounded-full bg-background/90 p-1 text-muted-foreground shadow hover:text-destructive"
                            aria-label="Remove image"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </>
                ) : (
                    <>
                        <ImagePlus className="h-8 w-8 text-muted-foreground" />
                        <div className="text-sm text-muted-foreground">
                            <span className="font-medium text-foreground">Click to upload</span> or
                            drag and drop
                        </div>
                        <p className="text-xs text-muted-foreground">JPG, PNG or WebP (max 2 MB)</p>
                    </>
                )}
            </div>

            <input
                ref={inputRef}
                type="file"
                accept="image/jpeg,image/png,image/webp"
                className="hidden"
                onChange={(e) => handleFiles(e.target.files)}
            />

            {error && <p className="mt-2 text-sm text-destructive">{error}</p>}
        </div>
    );
}
