import { Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';
import { Trans } from 'react-i18next';

import Wix from '@/assets/img/TooltipIcons/1.png';
import WordPress from '@/assets/img/TooltipIcons/2.png';
import Zapier from '@/assets/img/TooltipIcons/3.png';
import Shopify from '@/assets/img/TooltipIcons/4.png';
import Magento from '@/assets/img/TooltipIcons/5.png';
import Joomla from '@/assets/img/TooltipIcons/6.png';
import Drupal from '@/assets/img/TooltipIcons/7.png';
import WooCommerce from '@/assets/img/TooltipIcons/8.png';

import { HoverCard } from '../../features/landing/components/Possibilities/HoverCard';
import Services from '../../features/landing/components/Possibilities/Services/Services';
import { UiTypography } from '../ui';

import { cardItemPossibilitiesStyles } from './styles';

function SmallCarditem({ item }: any) {
  const imageList = [
    { image: Wix, alt: 'Wix' },
    { image: WordPress, alt: 'WordPress' },
    { image: Zapier, alt: 'Zapier' },
    { image: Shopify, alt: 'Shopify' },
    { image: Magento, alt: 'Magento' },
    { image: Joomla, alt: 'Joomla' },
    { image: Drupal, alt: 'Drupal' },
    { image: WooCommerce, alt: 'WooCommerce' },
  ];

  return (
    <Stack sx={cardItemPossibilitiesStyles.wrapper}>
      <Image src={item.imageSrc} alt="Card Image" width={80} height={80} />
      <Stack flexDirection="column">
        <UiTypography variant="h6" sx={cardItemPossibilitiesStyles.title}>
          <Trans i18nKey={item.title} />
        </UiTypography>
        <UiTypography
          variant="bodyText16"
          sx={cardItemPossibilitiesStyles.text}
        >
          <Trans i18nKey={item.text}>
            Інтегруйте
            <Services content={<HoverCard imageList={imageList} />}>
              звичні сервіси
            </Services>
            у кілька кліків
          </Trans>
        </UiTypography>
      </Stack>
    </Stack>
  );
}

export default SmallCarditem;
