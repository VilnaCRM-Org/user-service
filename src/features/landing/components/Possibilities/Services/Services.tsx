import {
  Tooltip,
  TooltipProps,
  Typography,
  styled,
  tooltipClasses,
} from '@mui/material';

import Wix from '@/assets/img/TooltipIcons/1.png';
import WordPress from '@/assets/img/TooltipIcons/2.png';
import Zapier from '@/assets/img/TooltipIcons/3.png';
import Shopify from '@/assets/img/TooltipIcons/4.png';
import Magento from '@/assets/img/TooltipIcons/5.png';
import Joomla from '@/assets/img/TooltipIcons/6.png';
import Drupal from '@/assets/img/TooltipIcons/7.png';
import WooCommerce from '@/assets/img/TooltipIcons/8.png';

import { HoverCard } from '../HoverCard';

import { servicesStyles } from './styles';

const HtmlTooltip = styled(({ className, ...props }: TooltipProps) => (
  // eslint-disable-next-line react/jsx-props-no-spreading
  <Tooltip {...props} classes={{ popper: className }} />
))(() => ({
  [`& .${tooltipClasses.tooltip}`]: {
    backgroundColor: '#fff',
    color: 'rgba(0, 0, 0, 0.87)',
    maxWidth: '330px',
    border: '1px solid  #D0D4D8',
    padding: '18px 24px',
    borderRadius: '8px',
    '@media (max-width: 1439.98px)': {
      display: 'none',
    },
  },
}));

function Services({ children }: any) {
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
    <HtmlTooltip
      placement="bottom"
      arrow
      title={<HoverCard imageList={imageList} />}
    >
      <Typography component="span" sx={servicesStyles.hoveredText}>
        {children}
      </Typography>
    </HtmlTooltip>
  );
}

export default Services;
