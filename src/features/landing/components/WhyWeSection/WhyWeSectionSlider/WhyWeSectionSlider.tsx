import { Box, MobileStepper } from '@mui/material';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import SwipeableViews from 'react-swipeable-views';

import Button from '@/components/ui/Button/Button';

import IWhyWeCardItem from '../../../types/why-we/types';
import scrollToRegistrationSection from '../../../utils/helpers/scrollToRegistrationSection';
import WhyWeSectionCardItem from '../WhyWeSectionCardItem/WhyWeSectionCardItem';

// TODO: Change Carousel to a newer one
export default function WhyWeSectionSlider({ cardItems }: { cardItems: IWhyWeCardItem[] }) {
  const { t } = useTranslation();
  const [activeStep, setActiveStep] = useState(0);
  const [, setCurrentActiveItem] = useState(cardItems[0]);
  const maxSteps = cardItems.length;

  const handleStepChange = (step: number) => {
    setCurrentActiveItem(cardItems[step]);
    setActiveStep(step);
  };

  const handleTryItOutButtonClick = () => {
    scrollToRegistrationSection();
  };

  return (
    <Box sx={{ padding: '0 15px 0 15px' }}>
      <SwipeableViews
        axis="x"
        index={activeStep}
        onChangeIndex={handleStepChange}
        enableMouseEvents
      >
        {cardItems.map((item) => (
          <Box
            key={item.id}
            sx={{
              display: 'flex',
              justifyContent: 'center',
              alignItems: 'center',
              height: '100%',
              maxHeight: '263px',
            }}
          >
            <WhyWeSectionCardItem
              cardItem={item}
              isSmall
              style={{
                width: '100%',
                height: '100%',
                maxHeight: '263px',
                overflow: 'hidden',
                margin: '0 5px 0 0',
                padding: '16px 18px 72px 16px',
              }}
            />
          </Box>
        ))}
      </SwipeableViews>

      <MobileStepper
        variant="dots"
        steps={maxSteps}
        position="static"
        activeStep={activeStep}
        nextButton={null}
        backButton={null}
        sx={{
          display: 'flex',
          justifyContent: 'center',
          marginTop: '22px',
          '& .MuiMobileStepper-dot:not(:last-child)': {
            marginRight: '24px',
          },
        }}
      />
      <Box sx={{ display: 'flex', justifyContent: 'center', marginBottom: '32px' }}>
        <Button
          customVariant="light-blue"
          onClick={handleTryItOutButtonClick}
          style={{ marginTop: '14px' }}
        >
          {t('why_we.button.try_it_out')}
        </Button>
      </Box>
    </Box>
  );
}
