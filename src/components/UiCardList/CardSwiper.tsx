import { Grid } from '@mui/material';
import { CSSProperties, useEffect, useRef } from 'react';
import { Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

import UiCardItem from '../UiCardItem';

import styles from './styles';
import 'swiper/css';
import 'swiper/css/pagination';
import { CardList } from './types';

function CardSwiper({ cardList }: CardList): React.ReactElement {
  const swiperRef: React.RefObject<HTMLElement> = useRef(null);

  useEffect(() => {
    const target: HTMLElement | null = document.querySelector('body');

    function isToolTip(node: Element): boolean {
      return node.role === 'tooltip' && node.classList.contains('base-Popper-root');
    }

    const config: MutationObserverInit = {
      childList: true,
    };

    const observer: MutationObserver = new MutationObserver((mutationsList: MutationRecord[]) => {
      mutationsList.forEach((mutation: MutationRecord) => {
        if (mutation.type === 'childList') {
          mutation.addedNodes.forEach((node: Node): void => {
            if (node instanceof Element && isToolTip(node)) {
              swiperRef.current!.style.pointerEvents = 'none';
            }
          });
          mutation.removedNodes.forEach((node: Node): void => {
            if (node instanceof Element && isToolTip(node)) {
              swiperRef.current!.style.pointerEvents = 'auto';
            }
          });
        }
      });
    });

    if (target) {
      observer.observe(target, config);
    }

    return () => observer.disconnect();
  }, []);

  const gridMobile: CSSProperties =
    cardList[0].type === 'smallCard' ? styles.gridSmallMobile : styles.gridLargeMobile;

  return (
    <Grid sx={gridMobile} ref={swiperRef as React.RefObject<HTMLDivElement>}>
      <Swiper
        pagination={{
          clickable: true,
        }}
        modules={[Pagination]}
        spaceBetween={12}
        slidesPerView={1.04}
        loop
        className="swiper-wrapper"
      >
        {cardList.map(item => (
          <SwiperSlide key={item.id}>
            <UiCardItem item={item} />
          </SwiperSlide>
        ))}
      </Swiper>
    </Grid>
  );
}

export default CardSwiper;
