import { Container } from '@mui/material';

import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { IWhyWeCardItem } from '@/features/landing/types/why-we/types';

import WhyWeSectionCardItemsRow from '../WhyWeSectionCardItemsRow/WhyWeSectionCardItemsRow';

interface IWhyWeSectionCardItemsProps {
  cardItems: IWhyWeCardItem[];
  tooltipIcons: string[];
}

export default function WhyWeSectionCardItems({
  cardItems,
  tooltipIcons,
}: IWhyWeSectionCardItemsProps) {
  const { isTablet } = useScreenSize();

  return (
    <Container sx={{ width: '100%', maxWidth: '1192px' }}>
      <WhyWeSectionCardItemsRow
        cardItems={cardItems.slice(0, 3)}
        style={{ padding: isTablet ? '0 32px 0 32px' : '0 12px 0 12px' }}
        tooltipIcons={tooltipIcons}
      />
      <WhyWeSectionCardItemsRow
        cardItems={cardItems.slice(3, 6)}
        style={{
          padding: isTablet ? '0 32px 0 32px' : '0 0 0 0',
          marginTop: '13px',
        }}
        tooltipIcons={tooltipIcons}
      />
    </Container>
  );
}
