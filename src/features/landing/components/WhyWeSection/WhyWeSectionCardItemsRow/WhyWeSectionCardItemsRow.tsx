import React from 'react';
import { Grid } from '@mui/material';

import { IWhyWeCardItem } from '@/features/landing/types/why-we/types';
import { WhyWeSectionCardItem } from '../WhyWeSectionCardItem/WhyWeSectionCardItem';
import { CustomTooltip } from '@/components/ui/CustomTooltip/CustomTooltip';

interface IWhyWeSectionCardItemsRowProps {
  cardItems: IWhyWeCardItem[];
  style?: React.CSSProperties;
  tooltipIcons: string[];
}

export function WhyWeSectionCardItemsRow({
  cardItems,
  style,
  tooltipIcons,
}: IWhyWeSectionCardItemsRowProps) {
  return (
    <Grid
      container
      sx={{
        ...style,
      }}
      spacing={'13px'}
    >
      {cardItems.map((cardItem) => {
        return (
          <Grid item key={cardItem.id} xs={4}>
            <CustomTooltip
              title={'Regular services'}
              text={'Integrate in a few clicks'}
              icons={tooltipIcons}
            >
              <WhyWeSectionCardItem cardItem={cardItem} />
            </CustomTooltip>
          </Grid>
        );
      })}
    </Grid>
  );
}
