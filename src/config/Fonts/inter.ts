import { NextFont } from 'next/dist/compiled/@next/font';
import localFont from 'next/font/local';

export const inter: NextFont = localFont({
  src: [
    {
      path: '../../assets/fonts/Inter/Inter-Regular.ttf',
      weight: '400',
      style: 'normal',
    },
    {
      path: '../../assets/fonts/Inter/Inter-Medium.ttf',
      weight: '500',
      style: 'normal',
    },
    {
      path: '../../assets/fonts/Inter/Inter-Bold.ttf',
      weight: '700',
      style: 'normal',
    },
  ],
});
