export const FLAG_LABELS: Record<string, string> = {
  impossible_jump: 'Impossible travel',
  rapid_clock: 'Rapid clock in/out',
  out_of_radius: 'Out of radius',
  gps_spoof: 'GPS spoofing',
  face_mismatch: 'Face mismatch',
  no_face: 'No face detected',
  overbreak: 'Overbreak',
};

export const FLAG_TONES: Record<string, 'red' | 'amber' | 'violet' | 'blue' | 'gray'> = {
  gps_spoof: 'red',
  impossible_jump: 'violet',
  rapid_clock: 'amber',
  out_of_radius: 'amber',
  face_mismatch: 'red',
  no_face: 'red',
  overbreak: 'red',
};
