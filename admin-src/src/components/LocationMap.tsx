import { useEffect, useMemo } from 'react';
import { Circle, CircleMarker, MapContainer, Marker, TileLayer, useMap } from 'react-leaflet';
import L from 'leaflet';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet/dist/leaflet.css';

const DefaultIcon = L.icon({
  iconUrl: markerIcon,
  iconRetinaUrl: markerIcon2x,
  shadowUrl: markerShadow,
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41],
});
L.Marker.prototype.options.icon = DefaultIcon;

export interface MapPin {
  latitude: number;
  longitude: number;
  radius_meters?: number;
}

const DEFAULT_RADIUS = 200;

function MapResize() {
  const map = useMap();

  useEffect(() => {
    const id = window.setTimeout(() => map.invalidateSize(), 80);
    return () => window.clearTimeout(id);
  }, [map]);

  return null;
}

function FitBounds({ points }: { points: [number, number][] }) {
  const map = useMap();

  useEffect(() => {
    if (points.length === 1) {
      map.setView(points[0], 16);
      return;
    }

    if (points.length > 1) {
      map.fitBounds(L.latLngBounds(points), { padding: [30, 30] });
    }
  }, [map, points]);

  return null;
}

export function LocationMap({
  requested,
  approved = null,
  className = 'h-64',
  legendLabel = 'Requested pin',
  showRadius = true,
}: {
  requested: MapPin;
  approved?: MapPin | null;
  className?: string;
  legendLabel?: string;
  showRadius?: boolean;
}) {
  const points = useMemo<[number, number][]>(() => {
    const result: [number, number][] = [[requested.latitude, requested.longitude]];

    if (approved) {
      result.push([approved.latitude, approved.longitude]);
    }

    return result;
  }, [requested.latitude, requested.longitude, approved?.latitude, approved?.longitude]);

  return (
    <div className="overflow-hidden rounded-lg border border-border">
      <div className={`relative w-full bg-slate-100 ${className}`}>
        <MapContainer
          center={[requested.latitude, requested.longitude]}
          zoom={16}
          className="absolute inset-0 z-0 h-full w-full"
          scrollWheelZoom
        >
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            url="https://tile.openstreetmap.org/{z}/{x}/{y}.png"
            referrerPolicy="strict-origin-when-cross-origin"
          />
          <MapResize />
          <FitBounds points={points} />

          {showRadius ? (
            <Circle
              center={[requested.latitude, requested.longitude]}
              radius={requested.radius_meters ?? DEFAULT_RADIUS}
              pathOptions={{ color: '#2563eb', fillColor: '#3b82f6', fillOpacity: 0.12, weight: 2 }}
            />
          ) : null}
          <Marker position={[requested.latitude, requested.longitude]} />

          {approved ? (
            <>
              <Circle
                center={[approved.latitude, approved.longitude]}
                radius={approved.radius_meters ?? DEFAULT_RADIUS}
                pathOptions={{ color: '#059669', fillColor: '#10b981', fillOpacity: 0.1, weight: 2 }}
              />
              <CircleMarker
                center={[approved.latitude, approved.longitude]}
                radius={8}
                pathOptions={{ color: '#047857', fillColor: '#059669', fillOpacity: 0.95, weight: 2 }}
              />
            </>
          ) : null}
        </MapContainer>
      </div>

      <div className="flex flex-wrap items-center gap-3 border-t border-border bg-card px-3 py-2 text-xs text-muted">
        <span className="inline-flex items-center gap-1.5 font-medium">
          <span className="h-2.5 w-2.5 rounded-full bg-blue-500" aria-hidden />
          {legendLabel}
        </span>
        {approved ? (
          <span className="inline-flex items-center gap-1.5 font-medium">
            <span className="h-2.5 w-2.5 rounded-full bg-emerald-600" aria-hidden />
            Current approved
          </span>
        ) : null}
        <span className="ml-auto font-mono tnum">
          {requested.latitude.toFixed(6)}, {requested.longitude.toFixed(6)}
        </span>
      </div>
    </div>
  );
}
