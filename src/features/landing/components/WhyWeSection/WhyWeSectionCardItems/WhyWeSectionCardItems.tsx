import { IWhyWeCardItem } from '@/features/landing/types/why-we/types';
import { Container } from '@mui/material';
import { WhyWeSectionCardItemsRow } from '../WhyWeSectionCardItemsRow/WhyWeSectionCardItemsRow';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

interface IWhyWeSectionCardItemsProps {
  cardItems: IWhyWeCardItem[];
  tooltipIcons: string[];
}

export function WhyWeSectionCardItems({ cardItems, tooltipIcons }: IWhyWeSectionCardItemsProps) {
  const { isTablet } = useScreenSize();

  return (
    <Container sx={{ width: '100%', maxWidth: '1192px' }}>
      <WhyWeSectionCardItemsRow
        cardItems={cardItems.slice(0, 3)}
        style={{ padding: isTablet ? '0 32px 0 32px' : '0 0 0 0' }}
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
