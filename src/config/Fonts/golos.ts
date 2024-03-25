import { NextFont } from 'next/dist/compiled/@next/font';
import localFont from 'next/font/local';

export const golos: NextFont = localFont({
  src: [
    {
      path: '../../assets/fonts/Golos/GolosText-Regular.ttf',
      weight: '400',
      style: 'normal',
    },
    {
      path: '../../assets/fonts/Golos/GolosText-Medium.ttf',
      weight: '500',
      style: 'normal',
    },
    {
      path: '../../assets/fonts/Golos/GolosText-SemiBold.ttf',
      weight: '600',
      style: 'normal',
    },
    {
      path: '../../assets/fonts/Golos/GolosText-Bold.ttf',
      weight: '700',
      style: 'normal',
    },
    {
      path: '../../assets/fonts/Golos/GolosText-ExtraBold.ttf',
      weight: '800',
      style: 'normal',
    },
    {
      path: '../../assets/fonts/Golos/GolosText-Black.ttf',
      weight: '900',
      style: 'normal',
    },
  ],
});
