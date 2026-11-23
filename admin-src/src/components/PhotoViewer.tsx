import { useEffect, useRef, useState } from 'react';
import { Loader2, X } from 'lucide-react';

interface PhotoViewerProps {
  url: string;
  alt: string;
  className?: string;
  fallbackText?: string;
}

export function PhotoViewer({ url, alt, className, fallbackText = 'No photo available' }: PhotoViewerProps) {
  const [objectUrl, setObjectUrl] = useState<string | null>(null);
  const [error, setError] = useState(false);
  const [notFound, setNotFound] = useState(false);
  const [zoomed, setZoomed] = useState(false);
  const objectUrlRef = useRef<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    setError(false);
    setNotFound(false);
    setObjectUrl(null);

    if (objectUrlRef.current) {
      URL.revokeObjectURL(objectUrlRef.current);
      objectUrlRef.current = null;
    }

    async function load() {
      try {
        const response = await fetch(url, {
          credentials: 'include',
          headers: { Accept: 'image/jpeg' },
        });

        if (!response.ok) {
          if (!cancelled) {
            if (response.status === 404) setNotFound(true);
            else setError(true);
          }
          return;
        }

        const blob = await response.blob();
        if (cancelled) return;

        const next = URL.createObjectURL(blob);
        objectUrlRef.current = next;
        setObjectUrl(next);
      } catch {
        if (!cancelled) setError(true);
      }
    }

    void load();

    return () => {
      cancelled = true;
      if (objectUrlRef.current) {
        URL.revokeObjectURL(objectUrlRef.current);
        objectUrlRef.current = null;
      }
    };
  }, [url]);

  useEffect(() => {
    if (!zoomed) return;
    const handler = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setZoomed(false);
    };
    window.addEventListener('keydown', handler);
    return () => window.removeEventListener('keydown', handler);
  }, [zoomed]);

  if (error) {
    return (
      <div className={`flex items-center justify-center bg-bg text-xs text-muted ${className ?? ''}`}>
        Could not load photo
      </div>
    );
  }

  if (notFound) {
    return (
      <div className={`flex items-center justify-center bg-bg text-xs text-muted ${className ?? ''}`}>
        {fallbackText}
      </div>
    );
  }

  if (!objectUrl) {
    return (
      <div className={`flex items-center justify-center bg-bg text-xs text-muted ${className ?? ''}`}>
        <Loader2 size={16} className="mr-2 animate-spin" />
        {fallbackText}
      </div>
    );
  }

  return (
    <>
      <img
        src={objectUrl}
        alt={alt}
        onClick={() => setZoomed(true)}
        className={`cursor-zoom-in object-contain ${className ?? ''}`}
      />
      {zoomed && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 sm:p-10"
          onClick={() => setZoomed(false)}
        >
          <img src={objectUrl} alt={alt} className="max-h-full max-w-full object-contain" />
          <button
            type="button"
            aria-label="Close photo"
            onClick={() => setZoomed(false)}
            className="absolute right-4 top-4 cursor-pointer rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
          >
            <X size={20} />
          </button>
        </div>
      )}
    </>
  );
}
